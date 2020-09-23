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

use App\Entity\OHLCV\History;
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

    /**
     * @var string
     */
    protected $symbol;

    /**
     * @var string
     */
    protected $provider;

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
In the first form, uses symbols list to import OHLCV data into database. Symbols list is usually a file named 
y_universe and saved in data/source directory. Other file may be used and must have symbols listed in its first column
titled 'Symbol'. Order of columns not important and other columns may be present. The command will go through symbols in
the y_universe and then will try to locate OHLCV price data files in data/source/OHLCV directory.
In the second form, only one symbol will be imported. Command will still rely on (default) y_universe file which must
have the symbol listed in it. 
Take note of the --provider option. If you omit it, price provider will not be assigned. This will lead to download 
of entire price data again by the price:sync command, when it is run for the first time for a given symbol. Price Sync 
uses query to retrieve prices marked with the current application-wide price provider, and if you have imported 
records with the null provider, it will assume that there is no price history.
 To lookup the current price provider, see config/services.yaml file under services.app.price_provider 
key, then lookup the constant for provider name in the price provider class. It is best to mimic current provider 
when importing histories, i.e. use --provider=YAHOO.

Each price data file must have header with the following columns: 
Date, Open, High, Low, Close, Volume. 
CSV files must be named as symbol_period.csv. Example: AAPL_d.csv or ABX_w.csv
EOT
        );

        $this->addUsage('[-v] [--offset=int] [--chunk=int] [data/source/y_universe.csv] [data/source/ohlcv]');
        $this->addUsage('[-v] [--offset=int] [--chunk=int] --symbol=FB [data/source/ohlcv]');

        $this->addArgument('list-file', InputArgument::OPTIONAL, 'path/to/file.csv with list of symbols to work on', self::MAIN_FILE);
        $this->addArgument('ohlcv-path', InputArgument::OPTIONAL, 'path/to/ohlcv data files', self::OHLCV_PATH);
        $this->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Starting offset, which includes header count. Header has offset=0');
        $this->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'Number of records to process in one chunk');
        $this->addOption('symbol', null, InputOption::VALUE_REQUIRED, 'Symbol to export');
        $this->addOption('provider', null, InputOption::VALUE_REQUIRED, 'Name of Price Provider');
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

        if ($input->getOption('symbol')) {
            $this->symbol = $input->getOption('symbol');
        } else {
            $this->symbol = null;
        }

        if ($provider = $input->getOption('provider')) {
            $this->provider = $provider;
        } else {
            $this->provider = null;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->utilities->pronounceStart($this, $output);

        $repository = $this->em->getRepository(Instrument::class);

        $csvMainReader = Reader::createFromPath($this->listFile, 'r');
        $csvMainReader->setHeaderOffset(0);
        $statement = new Statement();
        if ($this->symbol) {
            $statement = $statement->where(function($v) { return $v['Symbol'] == $this->symbol; });
        } else {
            if ($this->offset > 0) {
                $statement = $statement->offset($this->offset - 1);
            }
            if ($this->chunk > 0) {
                $statement = $statement->limit($this->chunk);
            }
        }

        $records = $statement->process($csvMainReader);

        foreach ($records as $key => $record) {
            $logMsg = sprintf('%4d %5s: ', $key, $record['Symbol']);
            $screenMsg = $logMsg;

            $instrument = $repository->findOneBySymbol($record['Symbol']);

            if ($instrument) {
                $dailyFile = sprintf('%s/%s_d.csv', $this->ohlcvPath, $record['Symbol']);
                if ($this->fileSystem->exists($dailyFile)) {
                    $importedLines = $this->importPrices($dailyFile, $instrument, new \DateInterval('P1D'), $this->provider);
                    $logMsg .= sprintf('%d daily price records imported ', $importedLines);
                    $screenMsg = $logMsg;
                } else {
                    $logMsg .= 'no daily file ';
                    $screenMsg = $logMsg;
                }

                $weeklyFile = sprintf('%s/%s_w.csv', $this->ohlcvPath, $record['Symbol']);
                if ($this->fileSystem->exists($weeklyFile)) {
                    $importedLines = $this->importPrices($weeklyFile, $instrument, new \DateInterval('P1W'), $this->provider);
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
     * @param string $provider
     * @return int $number
     * @throws \Exception
     */
    private function importPrices($file, $instrument, $period, $provider)
    {
        $ohlcvReader = Reader::createFromPath($file, 'r');
        $ohlcvReader->setHeaderOffset(0);
        $lines = $ohlcvReader->getRecords();

        foreach ($lines as $number => $line) {
            $History = new History();
            $History->setTimestamp(new \DateTime($line['Date']));
            $History->setOpen($line['Open']);
            $History->setHigh($line['High']);
            $History->setLow($line['Low']);
            $History->setClose($line['Close']);
            $History->setVolume((int)$line['Volume']);
            $History->setInstrument($instrument);
            $History->setTimeinterval($period);
            $History->setProvider($provider);

            $this->em->persist($History);
        }
        $this->em->flush();

        return $number;
    }
}