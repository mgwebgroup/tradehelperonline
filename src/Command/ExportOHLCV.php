<?php
/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Instrument;
use App\Entity\OHLCV\History;
use App\Exception\PriceHistoryException;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\UtilityServices;

class ExportOHLCV extends Command
{
    public $listPath = 'data/source/x_universe.csv';
    public $exportPath = 'data/source/ohlcv';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var UtilityServices
     */
    protected $utilities;

    /**
     * @var string
     */
    protected $provider;

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
     * @var Instrument
     */
    protected $instrument;

    /**
     * @var Reader
     */
    protected $csvReader;

    public function __construct(
        Filesystem $filesystem,
        RegistryInterface $doctrine,
        UtilityServices $utilities
    ) {
        $this->filesystem = $filesystem;
        $this->em = $doctrine;
        $this->utilities = $utilities;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('th:price:export');

        $this->setDescription('Exports prices stored on OHLCV format from database into csv files');

        $this->setHelp("Exports OHLCV price data stored in database into csv file(s). The command can work either with a list of symbols (specified as a .csv file), or with one symbol (specified as the --symbol option). When working with the list, --offset and --chunk options can point to only parts of the list. Header in the file must be present and starts with offset = 0. Only one column is necessary to be present in header: Symbol (case sensitive). You may have other columns as well. Order of columns is not important. Default path and file name: {$this->listPath}.\nPrice data will be exported as .csv files following this convention: SYMBOL_d|w|m|y.csv.\nIf you omit the --provider option, price records where provider field is set to null will be exported.\nIf there is an existing file with already exported prices, it will be backed up the with .bak extension.");

        $this->addUsage(
            '[--from-date] [--to-date] [--offset=int] [--chunk=int] [--interval=P1D] [--provider=YAHOO]
          --list=data/source/x_universe.csv data/source/ohlcv'
        );
        $this->addUsage(
            '[--from-date] [--to-date] [--interval=P1D] [--provider=YAHOO] --symbol=FB [data/source/ohlcv]'
        );

        $this->addArgument(
            'export-path',
            InputArgument::OPTIONAL,
            'Path/to/directory for price files to export',
            $this->exportPath
        );
        $this->addOption(
            'list-file',
            null,
            InputOption::VALUE_REQUIRED,
            'Path/to/file.csv with list of symbols to work on',
            $this->listPath
        );
        $this->addOption(
            'interval',
            null,
            InputOption::VALUE_REQUIRED,
            'Time interval to export OHLCV data for (daily, weekly, monthly, quarterly, yearly)',
            History::INTERVAL_DAILY
        );
        $this->addOption(
            'from-date',
            null,
            InputOption::VALUE_REQUIRED,
            'Start date of stored history'
        );
        $this->addOption('to-date', null, InputOption::VALUE_REQUIRED, 'End date of stored history');
        $this->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'Number of records to process in one chunk');
        $this->addOption(
            'offset',
            null,
            InputOption::VALUE_REQUIRED,
            'Starting offset in y_universe file. Header has offset=0'
        );
        $this->addOption(
            'provider',
            null,
            InputOption::VALUE_REQUIRED,
            'Name of Price Provider. Must match that in PriceProvider class'
        );
        $this->addOption('symbol', null, InputOption::VALUE_REQUIRED, 'Symbol to export');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($symbol = $input->getOption('symbol')) {
            $this->instrument = $this->em->getRepository(Instrument::class)->findOneBySymbol($symbol);
            if (empty($this->instrument)) {
                $output->writeln(sprintf('<error>ERROR: </error>Instrument for symbol %s is not imported', $symbol));
                exit(1);
            }
        }

        if ($listFile = $input->getOption('list-file')) {
            if (false === $this->filesystem->exists($listFile)) {
                $output->writeln(sprintf(
                    '<error>ERROR: </error>File for the symbols list could not be found. Looked for: %s',
                    $listFile
                ));
                exit(1);
            }

            $this->csvReader = Reader::createFromPath($listFile);
        }

        $exportPath = $input->getArgument('export-path');
        if (false === $this->filesystem->exists($exportPath)) {
            $output->writeln(sprintf('Could not find export path %s', $exportPath));
            exit(1);
        }

        try {
            $tempFile = $this->filesystem->tempnam($exportPath, 'mgweb');
            $this->filesystem->remove($tempFile);
        } catch (IOException $e) {
            $output->writeln(sprintf('<error>ERROR: </error>%s', $e->getMessage()));
            exit(1);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->utilities->pronounceStart($this, $output);

        try {
            $fromDate = $input->getOption('from-date') ? new DateTime($input->getOption('from-date')) : null;
            $toDate = $input->getOption('to-date') ? new DateTime($input->getOption('to-date')) : null;
        } catch (Exception $e) {
            $output->writeln(sprintf('<error>ERROR: </error>%s', $e->getMessage()));
            exit(1);
        }

        try {
            $intervalRaw = strtolower($input->getOption('interval'));
            $interval = History::getOHLCVInterval($intervalRaw);
        } catch (PriceHistoryException $e) {
            $output->writeln(sprintf('<error>ERROR: </error>%s', $e->getMessage()));
            exit(1);
        }

        $provider = $input->getOption('provider');
        $exportPath = $input->getArgument('export-path');
        $period = substr($intervalRaw, 0, 1);

        if ($this->instrument) {
            $records[] = ['Symbol' => $this->instrument->getSymbol()];
        } else {
            $this->csvReader->setHeaderOffset(0);
            $statement = new Statement();
            $offset = $input->getOption('offset');
            $chunk = $input->getOption('chunk');
            if ($offset > 0) {
                $statement = $statement->offset($offset - 1);
            }
            if ($chunk > 0) {
                $statement = $statement->limit($chunk);
            }

            $records = $statement->process($this->csvReader);
        }

        foreach ($records as $record) {
            /** @var Instrument $instrument */
            $instrument = $this->em->getRepository(Instrument::class)->findOneBySymbol($record['Symbol']);
            $symbol = $instrument->getSymbol();
            if (empty($instrument)) {
                $output->writeln(
                    '<warning>WARNING: </warning>Could not find instrument for symbol %s',
                    $record['Symbol']
                );
                continue;
            }

            $history = $this->em->getRepository(History::class)->retrieveHistory(
                $instrument,
                $interval,
                $fromDate,
                $toDate,
                $provider
            );
            if (empty($history)) {
                $output->writeln(sprintf(
                    '<warning>WARNING: </warning> Could not find price history for symbol %s, interval = %s',
                    $record['Symbol'],
                    $intervalRaw
                ));
                continue;
            }

            $exportFileName = sprintf('%s_%s.csv', $symbol, $period);
            $exportFile = $exportPath . '/' . $exportFileName;

            if ($this->filesystem->exists($exportFile)) {
                $backupFileName = $exportFile . '.bak';
                $this->filesystem->copy($exportFile, $backupFileName);
            }

            $csvWriter = Writer::createFromPath($exportFile, 'w');
            $header = ['Date', 'Open', 'High', 'Low', 'Close', 'Volume'];
            $csvWriter->insertOne($header);
            $csvWriter->insertAll(array_map(function ($v) {
                return [
                  $v->getTimestamp()->format('Y-m-d'),
                  $v->getOpen(),
                  $v->getHigh(),
                  $v->getLow(),
                  $v->getClose(),
                  $v->getVolume()
                ];
            }, $history));

            unset($history);

            $output->writeln(sprintf(
                '%5.5s: Exported %s price history into file %s ',
                $symbol,
                $intervalRaw,
                $exportFileName
            ));
        }

        $this->utilities->pronounceEnd($this, $output);

        return 0;
    }
}
