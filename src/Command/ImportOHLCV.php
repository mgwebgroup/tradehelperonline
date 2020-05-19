<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\OHLCVHistory;
use App\Service\UtilityServices;
use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Entity\Instrument;

class ImportOHLCV extends Command
{
    const MAIN_FILE = 'data/source/y_universe.csv';
    const OHLCV_PATH = 'data/source/ohlcv';

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var App\Service\Utilities
     */
    protected $utilities;

    /**
     * Full path to symbols list, which includes file name
     * @var string
     */
    protected $listFile;

    /**
     * Path to ohlcv price data csv files
     * @var string
     */
    protected $ohlcvPath;

    /**
     * Offset in list file to start from. Header offset = 0
     * @var integer
     */
    protected $offset;

    /**
     * Number of records to go over in the list file
     * @var integer
     */
    protected $chunk;

    /**
     * @var Symfony\Component\Filesystem\Filesystem
     */
    private $fileSystem;

    public function __construct(
        RegistryInterface $doctrine,
        UtilityServices $utilities,
        Filesystem $fileSystem
    ) {
        $this->em = $doctrine->getManager();
        $this->utilities = $utilities;
        $this->fileSystem = $fileSystem;
        
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('price:import');

        $this->setDescription(
          'Imports daily and weekly OHLCV price data from .csv files into database'
        );

        $this->setHelp(
          <<<'EOT'
Uses symbols list to import OHLCV data into database. Symbols list is usually a filed named y_universe and saved in 
data/source directory. Other file may be used and must have symbols in its first column. This command will go through
 symbols in the y_uniaverse and then will try to locate OHLCV price data files in data/source/OHLCV directory. Other 
 directory may be used. Each price data file must have header with the following columns: 
Date, Open, High, Low, Close, Volume. 
Order of columns not important and other columns may be present. CSV files must be named similar to: AAPL_d.csv or ABX_w.csv
EOT
        );

        $this->addUsage('[-v] [--offset=int] [--chunk=int] [data/source/y_universe.scv] [data/source/ohlcv]');

        $this->addArgument('list-file', InputArgument::OPTIONAL, 'path/to/file.csv with list of symbols to work on', self::MAIN_FILE);
        $this->addArgument('ohlcv-path', InputArgument::OPTIONAL, 'path/to/ohlcv data files', self::OHLCV_PATH);
        $this->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Starting offset, which includes header count. Header has offset=0');
        $this->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'Number of records to process in one chunk');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (!$this->fileSystem->exists($input->getArgument('list-file'))) {
            $logMsg = sprintf('File with symbols list was not found. Looked for `%s`', $input->getArgument('list-file'));
            $screenMsg = $logMsg;
            $this->utilities->logAndSay($output, $logMsg, $screenMsg);
            exit(1);
        } else {
            $this->listFile = $input->getArgument('list-file');
        }

        if (!$this->fileSystem->exists($input->getArgument('ohlcv-path'))) {
            $logMsg = sprintf('Path to ohlcv price data does not exist. Looked in `%s`', $input->getArgument('ohlcv-path'));
            $screenMsg = $logMsg;
            $this->utilities->logAndSay($output, $logMsg, $screenMsg);
            exit(1);
        } else {
            $this->ohlcvPath = trim($input->getArgument('ohlcv-path'), '/');
        }

        if ($input->getOption('offset')) {
            $this->offset = $input->getOption('offset');
        } else {
            $this->offset = 0;
        }

        if ($input->getOption('chunk')) {
            $this->chunk = $input->getOption('chunk');
        } else {
            $this->chunk = -1;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->utilities->pronounceStart($this, $output);

        $repository = $this->em->getRepository(Instrument::class);

        $csvMainReader = Reader::createFromPath($this->listFile, 'r');
        $csvMainReader->setHeaderOffset(0);
        $statement = new Statement();
        if ($this->offset > 0) {
            $statement = $statement->offset($this->offset - 1);
        }
        if ($this->chunk > 0) {
            $statement = $statement->limit($this->chunk);
        }
        $records = $statement->process($csvMainReader);

        foreach ($records as $key => $record) {
            $logMsg = sprintf('%4d %5s: ', $key, $record['Symbol']);
            $screenMsg = $logMsg;

            $instrument = $repository->findOneBySymbol($record['Symbol']);

            if ($instrument) {
                $dailyFile = sprintf('%s/%s_d.csv', $this->ohlcvPath, $record['Symbol']);
                if ($this->fileSystem->exists($dailyFile)) {
                    $importedLines = $this->importPrices($dailyFile, $instrument, new \DateInterval('P1D'));
                    $logMsg .= sprintf('%d daily price records imported ', $importedLines);
                    $screenMsg = $logMsg;
                } else {
                    $logMsg .= 'no daily file ';
                    $screenMsg = $logMsg;
                }

                $weeklyFile = sprintf('%s/%s_w.csv', $this->ohlcvPath, $record['Symbol']);
                if ($this->fileSystem->exists($weeklyFile)) {
                    $importedLines = $this->importPrices($weeklyFile, $instrument, new \DateInterval('P1W'));
                    $logMsg .= sprintf('%d weekly price records imported ', $importedLines);
                    $screenMsg = $logMsg;
                } else {
                    $logMsg .= 'no weekly file ';
                    $screenMsg = $logMsg;
                }
            } else {
                $logMsg .= 'instrument not imported ';
                $screenMsg = $logMsg;
            }

            $this->utilities->logAndSay($output, $logMsg, $screenMsg);
        }

        $this->utilities->pronounceEnd($this, $output);
    }

    /**
     * @param string $file
     * @param \App\Entity\Instrument $instrument
     * @param \DateInterval $period
     * @return int $number
     * @throws \Exception
     */
    private function importPrices($file, $instrument, $period)
    {
        $ohlcvReader = Reader::createFromPath($file, 'r');
        $ohlcvReader->setHeaderOffset(0);
        $lines = $ohlcvReader->getRecords();

        foreach ($lines as $number => $line) {
            $OHLCVHistory = new OHLCVHistory();
            $OHLCVHistory->setTimestamp(new \DateTime($line['Date']));
            $OHLCVHistory->setOpen($line['Open']);
            $OHLCVHistory->setHigh($line['High']);
            $OHLCVHistory->setLow($line['Low']);
            $OHLCVHistory->setClose($line['Close']);
            $OHLCVHistory->setVolume((int)$line['Volume']);
            $OHLCVHistory->setInstrument($instrument);
            $OHLCVHistory->setTimeinterval($period);

            $this->em->persist($OHLCVHistory);
        }
        $this->em->flush();

        return $number;
    }
}