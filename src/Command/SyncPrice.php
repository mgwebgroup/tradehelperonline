<?php
/**
 * This file is part of the Trade Helper package.
 *
 * (c) Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;


use App\Entity\Instrument;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Reader;

class SyncPrice extends Command
{
    const DEFAULT_PATH = 'data/source/y_universe.csv';

    const START_DATE = '2011-01-01';

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var League\Csv\Reader
     */
    protected $csv;

    /**
     * @var \App\Service\PriceHistory\OHLCV\Yahoo
     */
    protected $priceProvider;

    /**
     * @var Psr/Log/LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Symfony\Bridge\Doctrine\RegistryInterface $doctrine,
        \App\Service\PriceHistory\OHLCV\Yahoo $priceProvider,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->em = $doctrine->getManager();
        $this->priceProvider = $priceProvider;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('price:sync');

        $this->setDescription('Syncs up prices for a list of stocks from a price provider');

        $this->setHelp(
            <<<'EOT'
Uses a csv file with header and list of symbols to define list of symbols to work on. Symbols found in the csv file must
 also be imported as instruments. You can use data fixtures to import all instruments. Header must contain column titles
 contained in the current file data/source/y_universe.csv.
EOT
        );

        $this->addUsage('data/source/y_universe.csv');

        $this->addArgument('path', InputArgument::OPTIONAL, 'path/to/file.csv with list of symbols to work on', self::DEFAULT_PATH);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->em->getRepository(Instrument::class);

        $today = new \DateTime();

        $csvFile = $input->getArgument('path');
        $csv = Reader::createFromPath($csvFile, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        $queue = new ArrayCollection();

        foreach ($records as $key => $record) {
            $logMsg = sprintf('%s: ', $record['Symbol']);
            $options = ['interval' => 'P1D'];

            $instrument = $repository->findOneBySymbol($record['Symbol']);
            $exchange = $this->priceProvider->getExchangeForInstrument($instrument);
            $prevT = $exchange->calcPreviousTradingDay($today)->setTime(0,0,0);

            $criterion = new Criteria(Criteria::expr()->eq('symbol', $instrument->getSymbol()));

            // history exists? Download if missing.
            $lastPrice = $this->priceProvider->retrieveClosingPrice($instrument);

            if (!$lastPrice) {
                $logMsg .= 'No Price History found ';
                $fromDate = new \DateTime(self::START_DATE);

                $this->addMissingHistory($instrument, $fromDate, $today, $options);

                $lastPrice = $this->priceProvider->retrieveClosingPrice($instrument);

                $logMsg .= sprintf('History downloaded with last date %s ', $lastPrice->getTimestamp()->format('Y-m-d H:i:s'));
            }

            // gaps exist between today and last day of history? download missing history
            if ($lastPrice->getTimestamp() < $prevT) {
                $logMsg .= sprintf('Gap determined: lastP=%s prevT=%s ', $lastPrice->getTimestamp()->format('Y-m-d H:i:s'), $prevT->format('Y-m-d H:i:s'));
                $this->addMissingHistory($instrument, $lastPrice->getTimestamp()->setTime(0,0,0), $today, $options);

                $lastPrice = $this->priceProvider->retrieveClosingPrice($instrument);
                $logMsg .= sprintf('Added missing history lastP=%s ', $lastPrice->getTimestamp()->format('Y-m-d H:i:s'));
            }

            if($lastPrice->getTimestamp()->format('Ymd') == $prevT->format('Ymd')) {
                // this will put instruments into queue and download quotes in one shot later
                if ($queue->matching($criterion)->isEmpty()) {
                    $queue->add($instrument);
                }
            }

            // To do: this is optional. Should be done only if specified (force update option)
            if ($lastPrice->getTimestamp()->format('Ymd') == $today->format('Ymd')) {
                if ($queue->matching($criterion)->isEmpty()) {
                    $queue->add($instrument);
                }
            }

//            $this->logger->info($logMsg);
            $output->writeln(sprintf('%3d %5.5s %-30.40s', $key, $record['Symbol'], $record['Name']));
        }

        // check for one shot download and updated latest prices anyway:
        if (!$queue->isEmpty()) {
            $quotes = $this->priceProvider->getQuotes($queue->toArray());

            foreach ($quotes as $quote) {
                $instrument = $quote->getInstrument();
                if ($exchange->isOpen($today)) {
                    $this->priceProvider->saveQuote($instrument);
                    $this->priceProvider->addQuoteToHistory($quote);
                } else {
                    $this->priceProvider->removeQuote($instrument);
                    $closingPrice = $this->priceProvider->castQuoteToHistory($quote);
                    $this->priceProvider->addClosingPriceToHistory($closingPrice);
                }
            }
        }
    }

    private function addMissingHistory($instrument, $fromDate, $today, $options)
    {
        $history = $this->priceProvider->downloadHistory($instrument, $fromDate, $today, $options);

        // To do: implement chunking here
        $this->priceProvider->addHistory($instrument, $history);
    }
}