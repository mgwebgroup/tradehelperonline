<?php
/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Service\PriceHistory;

/**
 * Price provider can be any style of price data: OHLCV, ticks, japanese hoopla, etc.
 * Two types of price data are recognized: history (collection of closing prices) and quote.
 * There is a difference between a closing price and a quote. These are stored in different entities.
 * Closing price is the same type as any individual item of pricehistory and can be downloaded during
 *   market off-hours. Quote is its own type and can be downloaded when the market is open.
 */
interface PriceProviderInterface
{
	/**
	 * Downloads historical price information from a provider. Historical means prices
	 * from a given date and including last trading day before today. If today is a
	 * trading day, it will not be included. Use downloadQuote (for open trading hours),
	 * and downloadClosingPrice(for past trading hours).
     * Also, $toDate means not including, or up to <date>, not through <date>
	 * Downloaded history will be sorted from earliest date (the first element) to the
	 *  latest (the last element).
	 * @param App\Entity\Instrument $instrument
	 * @param DateTime $fromDate
	 * @param DateTime $toDate
	 * @param array $options (example: ['interval' => 'P1D'])
     * @return \App\Entity\<History Entity>[]
	 * @throws PriceHistoryException
	 */
	public function downloadHistory($instrument, $fromDate, $toDate, $options);

	/**
	 * Will add new history to the stored history.
	 * All records in old history which start from the earliest date in $history will be deleted, with the new
	 *  records from $history written in.
	 * @param App\Entity\Instrument $instrument
	 * @param \App\Entity\<History Entity>[]
	 */
 	public function addHistory($instrument, $history);

	/**
	  * Will export history from the given array into file system. Options must specify format.
	  * @param \App\Entity\<History Entity>[]
	  * @param string $path
	  * @param array $options
	  */
 	public function exportHistory($history, $path, $options);
 
 	/**
 	 * Retrieves price history for an instrument from storage.
 	 * @param App\Entity\Instrument $instrument
 	 * @param DateTime $fromDate
 	 * @param DateTime $toDate
 	 * @param array $options (example: ['interval' => 'P1D'])
 	 * @return array with elements of type App\Entity\<History Entity>
 	 */
 	public function retrieveHistory($instrument, $fromDate, $toDate, $options);
 
 	/**
 	 * Quotes are downloaded when a market is open
 	 * @param App\Entity\Instrument $instrument
 	 * @return App\Entity\<Quote Entity> when market is open or null if market is closed.
  	 */
 	public function downloadQuote($instrument);
 
 	/**
 	 * Saves given quote in storage. For any given instrument only one quote supposed to be saved in storage.
 	 * If this function is called with existing quote already in storage, existing quote will be removed and
 	 * new one saved.
 	 * @param App\Entity\Instrument $instrument
 	 * @param App\Entity\<Quote Entity> $quote
 	 */
 	public function saveQuote($instrument, $quote);

    /**
     * Unsets quote from instrument. Quote object is deleted from db.
     * @param App\Entity\Instrument $instrument
     */
 	public function removeQuote($instrument);
 
 	/**
 	 * Adds a quote object to array of history. No gaps allowed, i.e. if quote date would skip at least one trading day in history,
 	 *   no addition will be performed.
     * If quote date is not the same as today, nothing will be done (return null)
 	 * @param App\Entity\<Quote Entity> $quote.
     * If date on the quote coincides with the last date in history:
     *   Market open: last history element will be overwritten with the quote value.
     *   Market closed: nothing will be done.
 	 * @param array $history with elements compatible with chosen storage format (Doctrine Entities, csv records, etc.)
 	 *  OR optional. If not-passed (optional), then quote will be added directly to db history storage.
 	 *  If no stored history exists, nothing will be done.
 	 * @return
     *   on success: modified $history | true (history is in storage)
     *   nothing was done: null
     *   gap was determined: false
 	 */
 	public function addQuoteToHistory($quote, $history);
 
 	/**
 	 * Retrieves quote from storage. Only one quote per instrument is supposed to be in storage. See saveQuote above
 	 * @param App\Entity\Instrument $instrument
 	 * @return App\Entity\<Quote Entity>
 	 */
	public function retrieveQuote($instrument);

	/**
	 * Closing Prices are downloaded when market is closed and will return values for the closing price on last known trading day.
	 * This function is intended to be used same way as downloadQuote, EXCEPT it returns values when market is closed.
	 * @param App\Entity\Instrument $instrument
	 * @return App\Entity\<History Entity> when market is closed | null if market is open.
	 */
	public function downloadClosingPrice($instrument);

	/**
	 * Retrieves latest closing price from price history
	 * @param App\Entity\Instrument
	 * @return App\Entity\<History Entity> compatible with chosen storage format (Doctrine Entities, csv records, etc.
     * I.e. App\Entity\OHLCV\History) | null if history for an instrument does not exist in database
	 */
	public function retrieveClosingPrice($instrument);

	/**
	 * Adds item of price history on top of existing history. Similar to addQuoteToHistory gaps are not allowed, however
     * does not account for market being open or closed. Will return false if gap is determined. Or if closing price is
     * within history but earlier than the last record.
	 * @param App\Enitity\<History Entity> $closingPrice
	 * @param array $history with elements compatible with chosen storage format (Doctrine Entities, csv records, etc.)
 	 *  OR null. If null then quote will be added directly to db history storage.
     * @return
     *  history passed as non-empty array and closing price:
     *   coincides with last date in $history: $history with last record updated to the closing price
     *   nextT, no gap: array $history with added price
     *   nextT, with gap: bool false
     *   earlier than history or within the history but the last record: false
     *
     *  history was not passed, or passed as empty array and is in storage and closing price:
     *   coincides with last date in $history: bool true (history in storage gets updated with last record updated to the closing price)
     *   nextT, no gap: bool true (history in storage gets added the new record of closing price)
     *   nextT, with gap: bool false
     *   earlier than history or within the history but the last record: false
     *
     *  history was not passed, or passed as empty array and is not in storage
     *   bool true (history in storage gets added the new record of closing price)
	 */
	public function addClosingPriceToHistory($closingPrice, $history);

}