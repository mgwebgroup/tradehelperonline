<?php

/** @noinspection PhpInconsistentReturnPointsInspection */

/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Instrument;
use App\Entity\OHLCV\History;
use App\Service\UtilityServices;
use DateInterval;
use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\Exchange\Catalog;
use Doctrine\ORM\EntityManager;
use DateTime;

class PriceAudit extends Command
{
    public const MAIN_FILE = 'data/source/x_universe.csv';
    public const INTERVAL_DAILY = 'daily';
//    public const INTERVAL_WEEKLY = 'weekly';
//    public const INTERVAL_MONTHLY = 'monthly';
//    public const INTERVAL_QUARTERLY = 'quarterly';
//    public const INTERVAL_YEARLY = 'yearly';
    public const DAILY_BEGINNING = '2011-01-03';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var string
     */
    protected $symbol;

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
     * @var UtilityServices
     */
    protected $utilities;

    /**
     * Full path to symbols list, which includes file name
     * @var string
     */
    protected $listFile;

    /**
     * @var Filesystem
     */
    private $fileSystem;


    /**
     * @var DateInterval
     */
    private $interval;

    /**
     * @var string
     */
    private $intervalNormalized;

    /**
     * Price provider
     * @var string
     */
    private $provider;

    /** @var Catalog */
    private $catalog;

    /**
     * @var DateTime
     */
    private $fromDate;

    /**
     * @var DateTime
     */
    private $toDate;

    public function __construct(
        RegistryInterface $doctrine,
        UtilityServices $utilities,
        Filesystem $fileSystem,
        Catalog $catalog
    ) {
        $this->em = $doctrine->getManager();
        $this->utilities = $utilities;
        $this->fileSystem = $fileSystem;
        $this->catalog = $catalog;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('th:price:audit');

        $this->setDescription(
            'Performs price audits for gaps, old price data, etc.'
        );

        $this->setHelp(
            "Audits price data for a given interval and symbols list contained in y_universe, or just one symbol. Currently the audit checks beginning date for the price to match 2011-01-03, price gaps and either last price record has last trading day timestamp.\n Following error situations will be displayed:\n ... daily start=2013-01-10... - Start of price history does not match standard beginning date\n ... P date error at id=828626 ... - When going through consecutive dates in the trading calendar defined for the exchange in which symbol is trading on, a date mismatch in price history was determined. This usually happens when there is a gap or duplicate price entry.\n ...  Last P date=2020-05-15 ... - This means that last price in history is older than date of last Trading Day relative to today.\n To help correct the inconsistencies listed in the audit results use the following shell commands:\n 1. Convert all lines that have P date error into 0-separated file names of price .csv files:\n ```\n sed -ne '/P date error/p' list | cut -d ':' -f1 | sed -ne 's-^[0-9 ]*-data/source/ohlcv/-p' | sed -ne 's/$/_d.csv/p'  | tr '\\n' '\0' | xargs -0 -n1 echo\n ```\n 2. Convert all lines that have P date error into space separated stock symbols:\n ```\n sed -ne '/P date error/p' list | cut -d ':' -f1 | sed -ne 's-^[0-9 ]*--p' | tr '\\n' ' '\n ```\n 3. Convert same lines into comma separated quoted symbols:\n ```\n sed -ne '/P date error/p' list | cut -d ':' -f1 | sed -ne 's-^[0-9 ]*--p' | sed -ne \"s/^/'/p\" | sed -ne \"s/$/'/p\" | tr '\\n' ','\n```\n"
        );

        $this->addUsage(
            '[-v] [--offset=int] [--chunk=int] [--interval=daily] [--from=2019-01-02] [to=2019-12-31] 
          [data/source/x_universe.csv]'
        );
        $this->addUsage('[-v] [--offset=int] [--chunk=int] [--interval=daily] --symbol=FB ');

        $this->addArgument(
            'list-file',
            InputArgument::OPTIONAL,
            'path/to/file.csv with list of symbols to work on',
            self::MAIN_FILE
        );
        $this->addOption(
            'offset',
            null,
            InputOption::VALUE_REQUIRED,
            'Starting offset, which includes header count. Header has offset=0'
        );
        $this->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'Number of records to process in one chunk');
        $this->addOption('symbol', null, InputOption::VALUE_REQUIRED, 'Symbol to export');
        $this->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Interval to audit: only daily is supported');
        $this->addOption('provider', null, InputOption::VALUE_REQUIRED, 'Price provider to audit, i.e. yahoo');
        $this->addOption('from', null, InputOption::VALUE_REQUIRED, 'date from which to audit price');
        $this->addOption('to', null, InputOption::VALUE_REQUIRED, 'date from which to audit price');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (!$this->fileSystem->exists($input->getArgument('list-file'))) {
            $logMsg = sprintf(
                'File with symbols list was not found. Looked for `%s`',
                $input->getArgument('list-file')
            );
            $screenMsg = $logMsg;
            $this->utilities->logAndSay($output, $logMsg, $screenMsg);
            exit(1);
        } else {
            $this->listFile = $input->getArgument('list-file');
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

        if ($interval = $input->getOption('interval')) {
            switch ($interval) {
                case self::INTERVAL_DAILY:
                    $this->interval = new DateInterval('P1D');
                    $this->intervalNormalized = self::INTERVAL_DAILY;
                    break;
                default:
                    $this->interval = new DateInterval('P1D');
                    $this->intervalNormalized = self::INTERVAL_DAILY;
            }
        } else {
            $this->interval = new DateInterval('P1D');
            $this->intervalNormalized = self::INTERVAL_DAILY;
        }

        if ($provider = $input->getOption('provider')) {
            $this->provider = strtoupper($provider);
        } else {
            $this->provider = null;
        }

        if ($date = $input->getOption('from')) {
            $this->fromDate = new DateTime($date);
        } else {
            $this->fromDate = null;
        }

        if ($date = $input->getOption('to')) {
            $this->toDate = new DateTime($date);
        } else {
            $this->toDate = null;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->utilities->pronounceStart($this, $output);

        $instrumentRepository = $this->em->getRepository(Instrument::class);
        $priceRepository = $this->em->getRepository(History::class);

        $csv = Reader::createFromPath($this->listFile);
        $csv->setHeaderOffset(0);
        $statement = new Statement();
        if ($this->symbol) {
            $statement = $statement->where(function ($v) {
                return $v['Symbol'] == $this->symbol;
            });
        } else {
            if ($this->offset > 0) {
                $statement = $statement->offset($this->offset - 1);
            }
            if ($this->chunk > 0) {
                $statement = $statement->limit($this->chunk);
            }
        }

        $records = $statement->process($csv);

        foreach ($records as $key => $line) {
            $logMsg = sprintf('%4d %5s: ', $key, $line['Symbol']);
            $screenMsg = $logMsg;

            $instrument = $instrumentRepository->findOneBySymbol($line['Symbol']);

            if ($instrument) {
                $priceHistory = $priceRepository->retrieveHistory(
                    $instrument,
                    $this->interval,
                    $this->fromDate,
                    $this->toDate,
                    $this->provider
                );
                if ($priceHistory) {
                    $exchange = $this->catalog->getExchangeFor($instrument);
                    $status = [];
                    // daily interval
                    switch ($this->intervalNormalized) {
                        case self::INTERVAL_DAILY:
                            $firstRecord = $priceHistory[0];
                            if ($this->fromDate) {
                                $beginningDate = $this->fromDate->format('Y-m-d');
                            } else {
                                $beginningDate = self::DAILY_BEGINNING;
                            }

                            // check beginning date matches first date from history
                            if ($firstRecord->getTimestamp()->format('Y-m-d') != $beginningDate) {
                                $status['daily_start'] = sprintf(
                                    'daily_start=%s ',
                                    $firstRecord->getTimestamp()->format('Y-m-d')
                                );
                                $beginningDate = $firstRecord->getTimestamp()->format('Y-m-d');
                            }

                            // check for gaps within history
                            $tradingCalendar = $exchange->getTradingCalendar();
                            $tradingCalendar->getInnerIterator()
                              ->setStartDate(new DateTime($beginningDate))
                              ->setDirection(1)
                            ;
                            $tradingCalendar->rewind();
                            $id = [];
                            foreach ($priceHistory as $record) {
                                $datePriceHistory = $record->getTimestamp()->format('Y-m-d');
                                $dateTradingCalendar = $tradingCalendar->current()->format('Y-m-d');
                                if ($datePriceHistory != $dateTradingCalendar) {
                                    $id[] = $record->getId();
                                    break;
                                }
                                $tradingCalendar->next();
                            }
                            if (!empty($id)) {
                                $status['p_sync'] = sprintf('P date error at id=%s ', implode(',', $id));
                            }

                            // check if last date matches last T relative to today
                            $lastRecord = array_pop($priceHistory);
                            $datePriceHistory = $lastRecord->getTimestamp()->format('Y-m-d');
                            if ($this->toDate === null) {
                                $prevT = $exchange->calcPreviousTradingDay(new DateTime());
                                if ($datePriceHistory != $prevT->format('Y-m-d')) {
                                    $status['last_T'] = sprintf('Last P date=%s ', $datePriceHistory);
                                }
                            }

                            break;
                        // TODO: weekly interval
                        // ...

                        // TODO: monthly interval
                        //...

                        // TODO: quarterly interval
                        //...
                        default:
                    }

                    if (empty($status)) {
                        $logMsg .= 'ok';
                        $screenMsg = $logMsg;
                    } else {
                        foreach ($status as $message) {
                            $logMsg .= $message;
                        }
                        $screenMsg = $logMsg;
                    }
                } else {
                    $logMsg .= sprintf('no %s PH', $this->intervalNormalized);
                    $screenMsg = $logMsg;
                }
            } else {
                $logMsg .= 'instrument not imported ';
                $screenMsg = $logMsg;
            }
            $this->utilities->logAndSay($output, $logMsg, $screenMsg);
        }

        $this->utilities->pronounceEnd($this, $output);

        return 0;
    }
}
