<?php

namespace App\Command;

use App\Exception\PriceHistoryException;
use App\Service\UtilityServices;
use League\Csv\Reader;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
Use App\Entity\OHLCV\History;
use App\Entity\Instrument;
use League\Csv\Statement;

class ConvertOhlcvCommand extends Command
{
    protected static $defaultName = 'th:convert-ohlcv';

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var App\Service\Utilities
     */
    protected $utilities;

    /**
     * @var League/Csv/Reader
     */
    protected $csvReader;

    /**
     * @var App\Entity\Instrument
     */
    protected $instrument;

    /**
     * @var array
     */
    protected $targetFrames;

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


    public function __construct(
      RegistryInterface $doctrine,
      UtilityServices $utilities
    ) {
        $this->em = $doctrine->getManager();
        $this->utilities = $utilities;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setHelp(
          <<<EOT
This command will convert OHLCV price history from daily timeframe to a superlative one, like weekly, monthly, 
quarterly and yearly. It can work with either one symbol, or a list. One symbol is specified with --symbol option, 
whereas a list must be specified as argument with a path to file relative to project root. The list file must contain
 only one column: Symbol. All symbols must already be imported via th:instruments:import command.
EOT
        );
        $this
            ->setDescription('Converts daily ohlcv data stored in database to weekly, monthly, quarterly and yearly')
            ->addArgument('file', InputArgument::OPTIONAL, 'Index file with symbols to use')
            ->addOption('weekly', 'w', InputOption::VALUE_NONE, 'Convert from daily to weekly')
            ->addOption('monthly', 'm', InputOption::VALUE_NONE, 'Convert from daily to monthly')
            ->addOption('quarterly', null, InputOption::VALUE_NONE, 'Convert from daily to quarterly')
            ->addOption('yearly', 'y', InputOption::VALUE_NONE, 'Convert from daily to yearly')
            ->addOption('symbol', 's', InputOption::VALUE_REQUIRED, 'Work on a symbol, instead of the index file')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Starting offset, which includes header count. Header has offset=0')
            ->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'Number of records to process in one chunk')
        ;
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->utilities->pronounceStart($this, $output);
        try {
            if ($input->getArgument('file')) {
                $this->csvReader = Reader::createFromPath($input->getArgument('file'), 'r');
                $this->csvReader->setHeaderOffset(0);
            } elseif ($symbol = $input->getOption('symbol')) {
                $this->instrument = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => $symbol]);
                if (!$this->instrument) {
                    throw new \Exception(sprintf('Could not find instrument for symbol `%s`. Did you import it?',
                                                 $symbol));
                }
            } else {
                throw new \Exception('You must specify either the index file with symbols, or --symbol to work on.');
            }
            if ($input->getOption('weekly')) {
                $this->targetFrames[] = 'weekly';
            }
            if ($input->getOption('monthly')) {
                $this->targetFrames[] = 'monthly';
            }
            if ($input->getOption('quarterly')) {
                $this->targetFrames[] = 'quarterly';
            }
            if ($input->getOption('yearly')) {
                $this->targetFrames[] = 'yearly';
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
        } catch(\Exception $e) {
            $output->writeln(sprintf('<error>ERROR: </error>%s', $e->getMessage()));
            exit(1);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->csvReader) {
            $statement = new Statement();
            if ($this->offset > 0) {
                $statement = $statement->offset($this->offset - 1);
            }
            if ($this->chunk > 0) {
                $statement = $statement->limit($this->chunk);
            }
            $records = $statement->process($this->csvReader);
//            $records = $this->csvReader->getRecords();
            foreach ($records as $value) {
                $instrument = $this->em->getRepository(Instrument::class)->findOneBy(['symbol' => $value['Symbol']]);
                if ($instrument) {
                    $this->instrument = $instrument;
                    $logMsg = $this->convert();
                    $this->utilities->logAndSay($output, $logMsg, $logMsg);
                } else {
                    $output->writeln(sprintf('<error>ERROR: </error>Instrument `%s` was not found under imported symbols',
                                             $value['Symbol']));
                }
            }
        } else {
            $logMsg = $this->convert();
            $this->utilities->logAndSay($output, $logMsg, $logMsg);
        }
        $this->utilities->pronounceEnd($this, $output);
    }

    protected function convert()
    {
        // find the latest date present for the superlative time frame
        $priceRepository = $this->em->getRepository(History::class);
        $logMsg = sprintf('%s: converted to ', $this->instrument->getSymbol());
        foreach ($this->targetFrames as $targetFrame) {
            /** @var \DateInterval $targetInterval */
            $targetInterval = History::getOHLCVInterval(strtolower($targetFrame));
            $lastPrice = $priceRepository->findOneBy(['instrument' => $this->instrument, 'timeinterval' => $targetInterval], ['timestamp' => 'desc']);
            if (!$lastPrice) {
                $lastPrice = $priceRepository->findOneBy(['instrument' => $this->instrument, 'timeinterval' => History::getOHLCVInterval(History::INTERVAL_DAILY)], ['timestamp' => 'asc']);
            }
            if (!$lastPrice) {
                throw new PriceHistoryException(sprintf('No daily price data found for instrument `%s`', $this->instrument));
            }
            $lastDate = $lastPrice->getTimestamp();
            // Get daily (base) price history from db and process according to set targetFrame
            $dailyInterval = History::getOHLCVInterval(History::INTERVAL_DAILY);
            $dailyPriceHistory = $priceRepository->retrieveHistory($this->instrument, $dailyInterval, $lastDate);
            $priceHistoryInNewTimeFrame = [];
            $newHistoryItem = new History();
            $newHistoryItem->setTimeinterval($targetInterval);
            foreach ($dailyPriceHistory as $dailyItem) {
                if (isset($previousDailyPrice)) {
                    switch ($targetFrame) {
                        case History::INTERVAL_WEEKLY:
                            if ($dailyItem->getTimestamp()->format('N') < $previousDailyPrice->getTimestamp()
                                ->format('N')) {
                                $priceHistoryInNewTimeFrame[] = $newHistoryItem;
                                unset($previousDailyPrice);
                            } elseif ($dailyItem->getTimestamp()->format('N') > $previousDailyPrice->getTimestamp()
                                ->format('N')) {
                                $newHistoryItem->expandCandle($dailyItem);
                            } else {
                                unset($previousDailyPrice);
                            }
                            break;
                        case History::INTERVAL_MONTHLY:
                            if ($dailyItem->getTimestamp()->format('j') < $previousDailyPrice->getTimestamp()
                                ->format('j')) {
                                $priceHistoryInNewTimeFrame[] = $newHistoryItem;
                                unset($previousDailyPrice);
                            } elseif ($dailyItem->getTimestamp()->format('j') > $previousDailyPrice->getTimestamp()
                                ->format('j')) {
                                $newHistoryItem->expandCandle($dailyItem);
                            } else {
                                unset($previousDailyPrice);
                            }
                            break;
                        case History::INTERVAL_QUARTERLY:
                            // the following calcs yield 0 for Jan, Apr, July and Oct
                            // and yield 2 for months preceding beginning of the quarter.
                            $prev = ($previousDailyPrice->getTimestamp()->format('n')-1)%3;
                            $current = ($dailyItem->getTimestamp()->format('n')-1)%3;
                            if ( $prev == 2 && $current == 0) {
                                $priceHistoryInNewTimeFrame[] = $newHistoryItem;
                                unset($previousDailyPrice);
                            } else {
                                $newHistoryItem->expandCandle($dailyItem);
                            }
                            break;
                        case History::INTERVAL_YEARLY:
                            if ($dailyItem->getTimestamp()->format('z') < $previousDailyPrice->getTimestamp()
                                ->format('z')) {
                                $priceHistoryInNewTimeFrame[] = $newHistoryItem;
                                unset($previousDailyPrice);
                            } elseif ($dailyItem->getTimestamp()->format('z') > $previousDailyPrice->getTimestamp()
                                ->format('z')) {
                                $newHistoryItem->expandCandle($dailyItem);
                            } else {
                                unset($previousDailyPrice);
                            }
                            break;
                    }
                }
                if (!isset($previousDailyPrice)) {
                    $newHistoryItem = new History();
                    $newHistoryItem->setInstrument($dailyItem->getInstrument());
                    $newHistoryItem->setTimestamp($dailyItem->getTimestamp());
                    $newHistoryItem->setProvider($dailyItem->getProvider());
                    $newHistoryItem->setTimeinterval($targetInterval);
                    $newHistoryItem->setOpen($dailyItem->getOpen());
                    $newHistoryItem->setHigh($dailyItem->getHigh());
                    $newHistoryItem->setLow($dailyItem->getLow());
                    $newHistoryItem->setVolume($dailyItem->getVolume());
                    $newHistoryItem->setClose($dailyItem->getClose());
                }
                $previousDailyPrice = $dailyItem;
            }
            unset($previousDailyPrice);
            $priceHistoryInNewTimeFrame[] = $newHistoryItem;
            // delete old price record for the new time frame (use date)
            $oldRecords = $priceRepository->retrieveHistory($this->instrument, $targetInterval, $lastDate);
            foreach ($oldRecords as $oldRecord) {
                $this->em->remove($oldRecord);
            }
            // persist new price records for the new time frame
            foreach ($priceHistoryInNewTimeFrame as $newPriceHistoryInNewTimeFrame) {
                $this->em->persist($newPriceHistoryInNewTimeFrame);
            }

            $this->em->flush();
            $logMsg .= sprintf('%s ', $targetFrame);
        }
        return $logMsg;
    }
}
