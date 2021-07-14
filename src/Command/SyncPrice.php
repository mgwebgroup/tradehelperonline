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
use App\Service\PriceHistory\PriceProviderInterface;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Service\Exchange\Catalog;

/**
 * Class SyncPrice
 * On Screen chatter legend:
 * NoPH - No Price History exists in DB
 */
class SyncPrice extends Command
{
    public const DEFAULT_PATH = 'data/source/x_universe.csv';

    public const START_DATE = '2011-01-01';

    public const MIN_DELAY = 5;
    public const MAX_DELAY = 25;

    protected $em;
    protected $csv;
    protected $priceProvider;

    /**
     * Delay in seconds between queries to API
     * @var int
     */
    protected $delay;

    protected $catalog;

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

    protected $symbol;
    private $logger;

    public function __construct(
        RegistryInterface $doctrine,
        PriceProviderInterface $priceProvider,
        Catalog $catalog,
        LoggerInterface $logger
    ) {
        $this->em = $doctrine->getManager();
        $this->priceProvider = $priceProvider;
        $this->logger = $logger;
        $this->catalog = $catalog;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('th:price:sync');

        $this->setDescription('Syncs up prices for a list of stocks from a price provider');

        $this->setHelp(
            <<<'EOT'
Uses a csv file with header and list of symbols to define list of symbols to work on. Symbols found in the csv file must
 also be imported as instruments. You can use instruments:import command to import all instruments. Header must contain 
 column titles contained in the current file data/source/x_universe.csv.
 The command writes everything into application log, regardless of verbosity settings. No output is sent to the screen
 by default either. If you want screen output, use -v option.
 Price records in history may be considered as either a Quote or Price History. 
 Records with time which is not midnight are considered Quotes. Timezone is not saved in db, so the retrieved DateTime
 objects assume (and set) script's timezone according to the exchange. All downloaded records which are historical have 
 their time set to midnight.
 There are several scenarios possible:
 A) If market is open, to update last quote with the latest use --stillT-saveQ option.
 B) If market is closed, and you still have last records saved as Quotes, i.e. last update was done during prior T and 
 you ended up with a bunch of quotes saved for mid-trading day. These kinds of records are historical records today.
 Then use --prevT-QtoH option.
 C) If you are running this on trading day, sometime after midnight NYC time and before market open, all downloaded
 history records will have price for last T. In this case just run the command without the above 2 options.
 
 So it is best to run price update during trading hours and after market close but before midnight NYC with 
 --stillT-saveQ option.
 After midnight NYC time and before market open without these 2 options.
 
 To conserve memory, you can run this command with --chunk option:
 For 1 Core 1 G of RAM instance --chunk=50
 
EOT
        );

        $this->addUsage('[-v] [--prevT-QtoH] [--stillT-saveQ] [--delay=int|random] [--offset=int] [--chunk=int] [data/source/x_universe.csv]');

        $this->addArgument('path', InputArgument::OPTIONAL, 'list/of/symbols.csv', self::DEFAULT_PATH);
        $this->addOption('prevT-QtoH', null, InputOption::VALUE_NONE, 'If prev T in history is quote, will download history and will replace');
        $this->addOption('stillT-saveQ', null, InputOption::VALUE_NONE, 'Downloaded Quotes for today will keep replacing last P in history for today');
        $this->addOption('delay', null, InputOption::VALUE_REQUIRED, 'Delay in seconds between each query to API or word random for random delay no longer than 10 seconds.');
        $this->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'Number of records to process in one chunk');
        $this->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Starting offset, which includes header count. Header has offset=0');
        $this->addOption('symbol', null, InputOption::VALUE_REQUIRED, 'Symbol to download price data for');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($delay = $input->getOption('delay')) {
            if (is_numeric($delay)) {
                $this->delay = $delay;
            } elseif ('random' == $delay) {
                $this->delay = -1;
            } else {
                $this->delay = null;
            }
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

        if ($symbol = $input->getOption('symbol')) {
            $this->symbol = strtoupper($symbol);
        } else {
            $this->symbol = null;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->logger->info(sprintf('Command %s is starting', $this->getName()));

        $repository = $this->em->getRepository(Instrument::class);

        $today = new DateTime();

        try {
            $csvFile = $input->getArgument('path');
            $csv = Reader::createFromPath($csvFile);
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
        } catch (Exception $e) {
            $logMsg = $e->getMessage();
            $this->logger->error($logMsg);
            exit(1);
        }

        $queue = new ArrayCollection();

        foreach ($records as $key => $record) {
            $logMsg = sprintf('%3.3d %s ', $key, $record['Symbol']);
            $options = ['interval' => 'P1D'];
            $noopFlag = true;

            $instrument = $repository->findOneBySymbol($record['Symbol']);

            try {
                if ($instrument) {
                    $exchange = $this->catalog->getExchangeFor($instrument);

                    $prevT = $exchange->calcPreviousTradingDay($today)->setTime(0, 0, 0);

                    $criterion = new Criteria(Criteria::expr()->eq('symbol', $instrument->getSymbol()));

                    // history exists? Download if missing.
                    $lastPrice = $this->priceProvider->retrieveClosingPrice($instrument);

                    if (!$lastPrice) {
                        $logMsg .= 'noPH ';
                        $fromDate = new DateTime(self::START_DATE);

                        $this->addMissingHistory($instrument, $fromDate, $today, $options);
                        $noopFlag = false;

                        /** @var History $lastPrice */
                        $lastPrice = $this->priceProvider->retrieveClosingPrice($instrument);

                        $logMsg .= sprintf(
                            'saved%s...%s ',
                            $fromDate->format('Ymd'),
                            $lastPrice->getTimestamp()->format('Ymd')
                        );
                    }

                    // gaps exist between today and last day of history? download missing history
                    if ($lastPrice->getTimestamp() < $prevT) {
                        $logMsg .= sprintf('gap_after%s ', $lastPrice->getTimestamp()->format('Ymd'));
                        $gapStart = clone $lastPrice;

                        $this->addMissingHistory(
                            $instrument,
                            $lastPrice->getTimestamp()->setTime(0, 0),
                            $today,
                            $options
                        );

                        $noopFlag = false;

                        $lastPrice = $this->priceProvider->retrieveClosingPrice($instrument);

                        $logMsg .= sprintf(
                            'saved%s...%s ',
                            $gapStart->getTimestamp()->format('Ymd'),
                            $lastPrice->getTimestamp()->format('Ymd')
                        );
                    }

                    // If last price's time is not 0 hours, 0 minutes and 0 seconds, it is a quote and may need to be
                    // downloaded and replaced as history.
                    if ($lastPrice->getTimestamp()->format('Ymd') == $prevT->format('Ymd')) {
                        // need to check option here to replace quotes with history records
                        if (
                            $input->getOption('prevT-QtoH') && ($lastPrice->getTimestamp()->format(
                                'H'
                            ) + $lastPrice->getTimestamp()->format('i') + $lastPrice->getTimestamp()->format(
                                's'
                            ) > 0)
                        ) {
                            $this->addMissingHistory(
                                $instrument,
                                $lastPrice->getTimestamp()->setTime(0, 0),
                                $today,
                                $options
                            );

                            $noopFlag = false;

                            $logMsg .= sprintf('replacedQtoH ');
                        }

                        if ($input->getOption('stillT-saveQ')) {
                            if ($queue->matching($criterion)->isEmpty()) {
                                $queue->add($instrument);
                                $logMsg .= 'queued_for_Q ';
                            }
                        }
                    }

                    if (
                        $input->getOption('stillT-saveQ') && $lastPrice->getTimestamp()->format(
                            'Ymd'
                        ) == $today->format('Ymd')
                    ) {
                        if ($queue->matching($criterion)->isEmpty()) {
                            $queue->add($instrument);
                            $logMsg .= 'in_queue_forQ ';
                        }
                    }
                } else {
                    $logMsg .= 'not found';
                }
            } catch (PriceHistoryException $e) {
                switch ($e->getCode()) {
                    case 1:
                        $logMsg .= 'Missing exchange name ';
                        break;
                    case 2:
                    case 3:
                        $logMsg .= 'API_fail ';
                        // API maybe refusing connection in this case.
//                        $this->delay = rand(15,30);
                        break;
                    default:
                        $logMsg .= $e->getMessage();
                }
            } finally {
                if ($noopFlag) {
                    $logMsg .= 'NOOP';
                }
                $this->logger->notice($logMsg);
            }
        }

        // check for one shot download and updated latest prices anyway:
        if (!$queue->isEmpty()) {
            $logMsg = sprintf(PHP_EOL . 'Will now download quotes for %d symbols', $queue->count());
            $this->logger->notice($logMsg);

            $quotes = $this->priceProvider->getQuotes($queue->toArray());

            $logMsg = sprintf('Downloaded %d quotes', count($quotes));
            $this->logger->notice($logMsg);

            foreach ($quotes as $quote) {
                $instrument = $quote->getInstrument();
                $logMsg = sprintf('%s: ', $instrument->getSymbol());
                if ($exchange->isOpen($today)) {
                    $this->priceProvider->saveQuote($instrument, $quote);
                    $this->priceProvider->addQuoteToHistory($quote);

                    $logMsg .= sprintf('savedQ=%0.2f ', $quote->getClose());
                } else {
                    $this->priceProvider->removeQuote($instrument);
                    $closingPrice = $this->priceProvider->castQuoteToHistory($quote);
                    $this->priceProvider->addClosingPriceToHistory($closingPrice);

                    $logMsg .= sprintf('savedQ=%0.2f as Closing P ', $quote->getClose());
                }

                $this->logger->notice($logMsg);
            }
        }

        $this->logger->info(sprintf('Command %s finished', $this->getName()));

        return 0;
    }

    /**
     * @param $instrument
     * @param $fromDate
     * @param $today
     * @param $options
     */
    private function addMissingHistory($instrument, $fromDate, $today, $options)
    {
        if ($this->delay > 0) {
            sleep($this->delay);
        } elseif ($this->delay < 0) {
            sleep(rand(self::MIN_DELAY, self::MAX_DELAY));
        }

        $history = $this->priceProvider->downloadHistory($instrument, $fromDate, $today, $options);

        $this->priceProvider->addHistory($instrument, $history);
    }
}
