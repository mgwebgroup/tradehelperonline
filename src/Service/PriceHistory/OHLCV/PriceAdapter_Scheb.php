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


use App\Entity\OHLCVHistory;
use App\Entity\OHLCVQuote;
use App\Exception\PriceHistoryException;

class PriceAdapter_Scheb implements \App\Service\PriceHistory\PriceAdapterInterface
{
    /**
     * @var \Scheb\YahooFinanceApi\ApiClient
     */
    protected $priceProvider;

    public function __construct(\Scheb\YahooFinanceApi\ApiClient $priceProvider)
    {
        $this->priceProvider = $priceProvider;
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
                $OHLCVHistory = new OHLCVHistory();
                $OHLCVHistory->setOpen($v->getOpen());
                $OHLCVHistory->setHigh($v->getHigh());
                $OHLCVHistory->setLow($v->getLow());
                $OHLCVHistory->setClose($v->getClose());
                $OHLCVHistory->setVolume($v->getVolume());
                $OHLCVHistory->setTimestamp($v->getDate());
                $OHLCVHistory->setInstrument($data[0]);
                $OHLCVHistory->setTimeinterval($data[1]);
                $OHLCVHistory->setProvider(Yahoo::PROVIDER_NAME);
                $v = $OHLCVHistory;
            },
            [$instrument, $interval]
        );

        return $history;
    }

    public function getQuote($instrument)
    {
        $providerQuote = $this->priceProvider->getQuote($instrument->getSymbol());
        $interval = new \DateInterval('P1D');

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

    public function getQuotes($list)
    {
        // TODO: Implement getQuotes() method.
    }
}