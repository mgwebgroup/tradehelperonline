<?php
/**
 * This file is part of the Trade Helper package.
 *
 * (c) Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\PriceHistory\OHLCV;


use App\Entity\OHLCV\History;
use App\Entity\OHLCVQuote;
use App\Exception\PriceHistoryException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

class PriceAdapter_Scheb implements \App\Service\PriceHistory\PriceAdapterInterface
{
    /**
     * @var \Scheb\YahooFinanceApi\ApiClient
     */
    protected $priceProvider;

    /**
     * @var \App\Repository\InstrumentRepository
     */
    protected $instrumentRepository;

    public function __construct(
        \Scheb\YahooFinanceApi\ApiClient $priceProvider,
        \App\Repository\InstrumentRepository $instrumentRepository
    )
    {
        $this->priceProvider = $priceProvider;
        $this->instrumentRepository = $instrumentRepository;
    }

    public function getHistoricalData($instrument, $fromDate, $toDate, $options)
    {
        switch ($options['interval']) {
            case 'P1M':
                $apiInterval = $this->priceProvider::INTERVAL_1_MONTH;
                $interval = new \DateInterval(Yahoo::INTERVAL_MONTHLY);
                break;
            case 'P1W':
                $apiInterval = $this->priceProvider::INTERVAL_1_WEEK;
                $interval = new \DateInterval(Yahoo::INTERVAL_WEEKLY);
                break;
            case 'P1D':
                $apiInterval = $this->priceProvider::INTERVAL_1_DAY;
                $interval = new \DateInterval(Yahoo::INTERVAL_DAILY);
                break;
            default:
                throw new PriceHistoryException(sprintf('Illegal name for interval: `%s`', $options['interval']));
        }

        $history = $this->priceProvider->getHistoricalData($instrument->getSymbol(), $apiInterval, $fromDate, $toDate);
        array_walk(
            $history,
            function (&$v, $k, $data) {
                $History = new History();
                $History->setOpen($v->getOpen());
                $History->setHigh($v->getHigh());
                $History->setLow($v->getLow());
                $History->setClose($v->getClose());
                $History->setVolume($v->getVolume());
                $History->setTimestamp($v->getDate());
                $History->setInstrument($data[0]);
                $History->setTimeinterval($data[1]);
                $History->setProvider(Yahoo::PROVIDER_NAME);
                $v = $History;
            },
            [$instrument, $interval]
        );

        return $history;
    }

    public function getQuote($instrument)
    {
        $providerQuote = $this->priceProvider->getQuote($instrument->getSymbol());

        return $this->castProviderQuoteToAppQuote($providerQuote);
    }

    public function getQuotes($list)
    {
        $symbolsList = [];
        foreach ($list as $instrument) {
            $symbolsList[] = $instrument->getSymbol();
        }

        $providerQuotes = $this->priceProvider->getQuotes($symbolsList);

        $quotesList = [];
        foreach ($providerQuotes as $providerQuote) {
            $quotesList[] = $this->castProviderQuoteToAppQuote($providerQuote);
        }

        return $quotesList;
    }

    /**
     * Extracts values from Scheb API Quote object into this App's Quote object
     * @param Scheb\YahooFinanceApi\Results\Quote $providerQuote
     * @return OHLCVQuote $quote
     * @throws \Exception
     */
    private function castProviderQuoteToAppQuote($providerQuote)
    {
        $interval = new \DateInterval('P1D');

        $instrument = $this->instrumentRepository->findOneBySymbol($providerQuote->getSymbol());

        if ($instrument) {
            $quote = new OHLCVQuote();
            $quote->setInstrument($instrument);
            $quote->setProvider(Yahoo::PROVIDER_NAME);
            $quote->setTimestamp($providerQuote->getRegularMarketTime());
            $quote->setTimeinterval($interval);
            $quote->setOpen($providerQuote->getRegularMarketOpen());
            $quote->setHigh($providerQuote->getRegularMarketDayHigh());
            $quote->setLow($providerQuote->getRegularMarketDayLow());
            $quote->setClose($providerQuote->getRegularMarketPrice());
            $quote->setVolume($providerQuote->getRegularMarketVolume());

            return $quote;
        }
    }
}