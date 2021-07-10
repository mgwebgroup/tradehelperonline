<?php

/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Instrument;
use App\Service\Exchange\Equities\NASDAQ;
use App\Service\Exchange\Equities\NYSE;
use App\Service\UtilityServices;
use Doctrine\ORM\EntityManager;
use Exception;
use League\Csv\Statement;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\Filesystem\Filesystem;

class ImportInstruments extends Command
{
    /**
     * Default list to import
     */
    public const MAIN_FILE = 'data/source/y_universe.csv';

    /**
     * These two designate which exchange a particular symbol belongs to
     */
    public const NASDAQ_FILE = 'data/source/nasdaqlisted.csv';
    public const NYSE_FILE = 'data/source/otherlisted.csv';

    /**
     * Used to save symbol in temporary csv file
     */
    public const TEMP_FILE = 'var/cache/temp.csv';

    /**
     * All symbols must have a name to them. This is the default name if --name option is absent
     */
    public const SYMBOL_NAME = 'Default Symbol name';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Clear existing database of all instruments
     * @var bool
     */
    protected $clearDb;

    /**
     * If instrument is already imported overwrite with new values
     * @var bool
     */
    protected $overwrite;

    /**
     * @var NASDAQ
     */
    private $NASDAQ;

    /**
     * @var NYSE
     */
    private $NYSE;

    /**
     * @var UtilityServices
     */
    private $utilities;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $symbol;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(
        RegistryInterface $doctrine,
        UtilityServices $utilities,
        NASDAQ $NASDAQ,
        NYSE $NYSE,
        Filesystem $filesystem
    ) {
        $this->utilities = $utilities;
        $this->em = $doctrine->getManager();
        $this->NASDAQ = $NASDAQ;
        $this->NYSE = $NYSE;
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('th:instruments:import');

        $this->setDescription('Imports list of instruments into database');

        $this->setHelp("You must have nasdaqlisted.csv and otherlisted.csv files saved in data/source directory in order to be able to use this command! These files specify every stock symbol that is traded either on NYSE or NASDAQ exchange. See data/source/README.md file to see how to update them.\nIn the first form of this command instruments to import are taken from the y_universe file. It simply serves as a source list to work on. It is saved in data/source/ directory. You can use other file, just make sure it has the following headers:\nSymbol,Name,Industry.\nOrder of columns is not important.\nIn the second form you don't specify the symbols list, but only one individual symbol. y_universe file is not necessary to exist, however the 2 exchange files must be present. Name of the traded company will be searched in these lists. If you specify --name option, name of the traded company will be overwritten.");

        $this->addUsage('[-v] [--clear-db=false] [--overwrite=false] [data/source/x_universe.csv]');
        $this->addUsage('[-v] [--clear-db=false] [--overwrite=false] --symbol=TST [--name="Test symbol"]');

        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'path/to/file.csv with list of symbols to work on',
            self::MAIN_FILE
        );
        $this->addOption(
            'clear-db',
            null,
            InputOption::VALUE_OPTIONAL,
            'Will clear all instruments from database before import',
            false
        );
        $this->addOption(
            'overwrite',
            null,
            InputOption::VALUE_OPTIONAL,
            'If instrument is already imported will override its values',
            false
        );
        $this->addOption(
            'symbol',
            null,
            InputOption::VALUE_REQUIRED,
            'Import one symbol. 
            It must be listed in one of the exchange data files: nasdaqlisted.csv or otherlisted.csv'
        );
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Symbol name');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->clearDb = $input->getOption('clear-db') ? true : false;
        $this->overwrite = $input->getOption('overwrite') ? true : false;
        $this->path = $input->getArgument('path');
        $this->symbol = $input->getOption('symbol') ? : null;
        $this->name = $input->getOption('name') ? : null;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->utilities->pronounceStart($this, $output);

        $repository = $this->em->getRepository(Instrument::class);

        if ($this->clearDb) {
            $repository->deleteInstruments();
        }

        try {
            if ($this->symbol) {
                $csvWriter = Writer::createFromPath(self::TEMP_FILE, 'w');
                $header = ['Symbol', 'Name'];
                $record = [$this->symbol, $this->name];
                $csvWriter->insertOne($header);
                $csvWriter->insertOne($record);
                $csvMainFile = self::TEMP_FILE;
            } else {
                $csvMainFile = $this->path;
            }

            $csvMainReader = Reader::createFromPath($csvMainFile);
            $csvMainReader->setHeaderOffset(0);
            $records = $csvMainReader->getRecords();

            $nasdaqReader = Reader::createFromPath(self::NASDAQ_FILE);
            $nyseReader = Reader::createFromPath(self::NYSE_FILE);

            foreach ($records as $key => $record) {
                $logMsg = sprintf('%3.3d %s: ', $key, $record['Symbol']);
                $screenMsg = $logMsg;

                // TODO: include check for symbol validity with the price provider here
                // ...

                $statement = Statement::create(function ($value, $key, $iterator) use ($record) {
                    return $value[0] == $record['Symbol'];
                });
                $inNasdaq = $statement->process($nasdaqReader);
                $inNyse = $statement->process($nyseReader);
                if ($inNasdaq->count() > 0) {
                    $exchange = $this->NASDAQ;
                } elseif ($inNyse->count() > 0) {
                    $exchange = $this->NYSE;
                } else {
                    $exchange = null;
                }

                if (empty($record['Name'])) {
                    $statement = Statement::create(function ($value, $key, $iterator) use ($record) {
                        if ($value[0] == $record['Symbol']) {
                            return $value;
                        }
                    });

                    $exchangeLists = [$nyseReader, $nasdaqReader];
                    foreach ($exchangeLists as $exchangeList) {
                        $resultSet = $statement->process($exchangeList);
                        foreach ($resultSet as $line) {
                            $record['Name'] = $line[1];
                            break;
                        }
                    }
                    if (empty($record['Name'])) {
                        $record['Name'] = self::SYMBOL_NAME;
                    }
                }

                if ($exchange) {
                    $action = 'skipped';
                    $instrument = $repository->findOneBySymbol($record['Symbol']);
                    if (empty($instrument)) {
                        $instrument = new Instrument();
                        $action = 'imported';
                    }
                    if ($instrument->getSymbol() == $record['Symbol']) {
                        $logMsg .= 'already imported ';
                    }
                    if ($this->overwrite || 'imported' == $action) {
                        $instrument->setSymbol(strtoupper($record['Symbol']));
                        $instrument->setExchange($exchange->getExchangeName());
                        $instrument->setName($record['Name']);
                        $this->em->persist($instrument);
                    }
                    if ($this->overwrite) {
                        $action = 'overwritten';
                    }

                    $logMsg .= $action;
                    $screenMsg = $logMsg;

                    unset($instrument);
                } else {
                    $logMsg .= 'No Exchange found! Will not be imported.';
                    $screenMsg = $logMsg;
                }
                $this->utilities->logAndSay($output, $logMsg, $screenMsg);
            }
            $this->em->flush();

            if ($this->symbol) {
                $this->filesystem->remove(self::TEMP_FILE);
            }
        } catch (Exception $e) {
            $logMsg = $e->getMessage();
            $screenMsg = $logMsg;
            $this->utilities->logAndSay($output, $logMsg, $screenMsg);
        }

        $this->utilities->pronounceEnd($this, $output);

        return 0;
    }
}
