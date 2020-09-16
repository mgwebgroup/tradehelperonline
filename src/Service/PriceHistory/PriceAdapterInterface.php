<?php
/**
 * This file is part of the Trade Helper package.
 *
 * (c) Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\PriceHistory;
/**
 * Classes that implement this interface must be created together when new Price Provider vendor package is assimilated.
 * Price Adapter classes are then injected as services into this package's Price Provider's constructor.
 */
interface PriceAdapterInterface
{
    /**
     * Downloads historical price data
     * @param App\Entity\Instrument $instrument
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @param array $options
     * @return App\Entity\OHLCV\History[]
     */
    public function getHistoricalData($instrument, $fromDate, $toDate, $options);

    /**
     * Downloads price quote
     * @param App\Entity\Instrument $instrument
     * @return App\Entity\OHLCVQuote $quote
     */
    public function getQuote($instrument);

    /**
     * Downloads several price quotes
     * @param App\Entity\Instrument[] $list
     * @return App\Entity\OHLCVQuote[]
     */
    public function getQuotes($list);
}
