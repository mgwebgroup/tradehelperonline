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

use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
// use Scheb\YahooFinanceApi\Exception\ApiException;
use App\Entity\OHLCVHistory;
use App\Exception\PriceHistoryException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Scheb\YahooFinanceApi\Results\Quote;
use App\Entity\OHLCVQuote;
// use Doctrine\Common\Persistence\ManagerRegistry;
// use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Instrument;


class Yahoo implements \App\PriceHistory\PriceProviderInterface
{
	const PROVIDER_NAME = 'YAHOO';

	/**
	* Currently supported intervals from the Price Provider 
	* These follow interval_spec for the \DateInterval class
	*/
	public $intervals = [
		// 'PT1M',
		// 'PT2M',
		// 'PT3M',
		// 'PT3M',
		// 'PT6M',
		// 'PT10M',
		// 'PT15M',
		// 'PT30M',
		// 'PT60M',
		// 'PT120M',
		'P1D',
		'P1W',
		'P1M',
		// 'P1Y',
	]; 

	private $priceProvider;

	private $saveQuotePath;

	private $exchangeNYSE;
	private $exchangeNASDAQ;
	private $exchangeAMEX;

	public $em;

	// public $doctrine;

 	/**
 	 * {@inheritDoc}
 	 */
	public function __construct(
        \App\Service\Exchange\NYSE $exchangeNYSE,
//        \App\Service\Exchange\NASDAQ $exchangeNASDAQ,
//        \App\Service\Exchange\AMEX $exchangeAMEX,
        RegistryInterface $registry
    ) {
        $this->exchangeNYSE = $exchangeNYSE;
        $this->priceProvider = ApiClientFactory::createApiClient();
		// $this->doctrine = $registry;
		$this->em = $registry->getManager();
	}

	/**
	 * {@inheritDoc}
	 * @param array $options ['interval' => 'P1M|P1W|P1D' ]
	 */
	public function downloadHistory($instrument, $fromDate, $toDate, $options)
	{
		if ('test' == $_SERVER['APP_ENV'] && isset($_SERVER['TODAY'])) {
			$today = $_SERVER['TODAY'];
		} else {
			$today = date('Y-m-d');
		}
		// var_dump($today); exit();
		// see if $toDate is today
		if ($toDate->format('Y-m-d') == $today) {
			$toDate = $this->exchangeEquities->calcPreviousTradingDay($toDate);
		} elseif ($toDate->format('U') > strtotime($today)) {
			$toDate = $this->exchangeEquities->calcPreviousTradingDay(new \DateTime($today));
		}

		// test for exceptions:
		if ($toDate->format('Y-m-d') == $fromDate->format('Y-m-d')) {
			throw new PriceHistoryException(sprintf('$fromDate %s is equal to $toDate %s', $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')));
		}
		if ($toDate->format('U') < $fromDate->format('U')) {
			throw new PriceHistoryException(sprintf('$toDate %s is earlier than $fromDate %s', $toDate->format('Y-m-d'), $fromDate->format('Y-m-d')));	
		}
		$hours = $toDate->diff($fromDate)->format('h'); // Hours, numeric
		// check if $toDate and $fromDate are on the same weekend, then except if ()
		if ($fromDate->format('w') == 6 && $toDate->format('w') == 0 && $hours <= 48 ) {
			throw new PriceHistoryException(sprintf('$fromDate %s and $toDate %s are on the same weekend.', $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')));
		}
		// check if $toDate or $fromDate is a long weekend (includes a contiguous holiday, then except
		if ($hours <= 72 && ((!$this->exchangeEquities->isTradingDay($fromDate) && $toDate->format('w') == 0) || ($fromDate->format('w') == 6 && !$this->exchangeEquities->isTradingDay($toDate)) )) {
			throw new PriceHistoryException(sprintf('$fromDate %s and $toDate %s are on the same long weekend.', $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')));
		}


		if (isset($options['interval']) && in_array($options['interval'], $this->intervals)) {
			switch ($options['interval']) {
				case 'P1M':
					$apiInterval = ApiClient::INTERVAL_1_MONTH;
					$interval = new \DateInterval('P1M');
					break;
				case 'P1W':
					$apiInterval = ApiClient::INTERVAL_1_WEEK;
					$interval = new \DateInterval('P1W');
					break;
				case 'P1D':
				default:
					$apiInterval = ApiClient::INTERVAL_1_DAY;
					$interval = new \DateInterval('P1D');
			}
		} else {
			$apiInterval = ApiClient::INTERVAL_1_DAY;
		}

		$history = $this->priceProvider->getHistoricalData($instrument->getSymbol(), $apiInterval, $fromDate, $toDate);
		// var_dump($history);
		array_walk($history, function(&$v, $k, $data) {
			$OHLCVHistory = new OHLCVHistory();
			$OHLCVHistory->setOpen($v->getOpen());
			$OHLCVHistory->setHigh($v->getHigh());
			$OHLCVHistory->setLow($v->getLow());
			$OHLCVHistory->setClose($v->getClose());
			$OHLCVHistory->setVolume($v->getVolume());
			$OHLCVHistory->setTimestamp($v->getDate());
			$OHLCVHistory->setInstrument($data[0]);
			$OHLCVHistory->setTimeinterval($data[1]);
			$OHLCVHistory->setProvider(self::PROVIDER_NAME);
			$v = $OHLCVHistory;
		}, [$instrument, $interval]);

		// make sure elements are ordered from oldest date to the latest
		$this->sortHistory($history);

		return $history;
	}

 	/**
 	 * {@inheritDoc}
 	 */
	public function addHistory($instrument, $history)
	{
		if (!empty($history)) {
			// delete existing OHLCV History for the given instrument from history start date to current date
			$OHLCVRepository = $this->em->getRepository(OHLCVHistory::class);
			// var_dump(get_class($OHLCVRepository)); exit();
			$fromDate = $history[0]->getTimestamp();
			$interval = $history[0]->getTimeinterval();
			$OHLCVRepository->deleteHistory($instrument, $fromDate, null, $interval, self::PROVIDER_NAME);

			// $em = $this->doctrine->getManager();

			// save the given history
			foreach ($history as $record) {
            	$this->em->persist($record);
        	}

        	$this->em->flush();
		}
	}
 
	/**
	 * {@inheritDoc}
	 * @param array $options ['interval' => 'P1M|P1W|P1D' ]
	 */
 	public function exportHistory($history, $path, $options)
 	{
 		throw new PriceHistoryException('exportHistory is not yet implemented.');
 	}
 
	/**
	 * {@inheritDoc}
	 * @param array $options ['interval' => 'P1M|P1W|P1D' ]
	 */
 	public function retrieveHistory($instrument, $fromDate, $toDate, $options)
 	{
 		if (isset($options['interval']) && !in_array($options['interval'], $this->intervals)) {
 			throw new PriceHistoryException('Requested interval `%s` is not in array of serviced intervals.', $options['interval']);
 		} elseif (isset($options['interval'])) {
 			$interval = new \DateInterval($options['interval']);
 		} else {
 			$interval = new \DateInterval('P1D');
 		}

 		$OHLCVRepository = $this->em->getRepository(OHLCVHistory::class);

 		return $OHLCVRepository->retrieveHistory($instrument, $fromDate, $toDate, $interval, self::PROVIDER_NAME);
 	}
 
 	/**
 	 * {@inheritDoc}
 	 */
 	public function downloadQuote($instrument) {
 		if ('test' == $_SERVER['APP_ENV'] && isset($_SERVER['TODAY'])) {
			$today = $_SERVER['TODAY'];
		} else {
			$today = date('Y-m-d H:i:s');
		}

		$dateTime = new \DateTime($today);
		// var_dump($dateTime); exit();
		if (!$this->exchangeEquities->isOpen($dateTime)) return null; 

		$providerQuote = $this->priceProvider->getQuote($instrument->getSymbol());
		$interval = new \DateInterval('P1D');

		if (!($providerQuote instanceof Quote)) throw new PriceHistoryException('Returned provider quote is not instance of Scheb\YahooFinanceApi\Results\Quote');

		$quote = new OHLCVQuote();

        $quote->setInstrument($instrument);
        $quote->setProvider(self::PROVIDER_NAME);
        $quote->setTimestamp($providerQuote->getRegularMarketTime());
        $quote->setTimeinterval($interval);
        $quote->setOpen($providerQuote->getRegularMarketOpen());
        $quote->setHigh($providerQuote->getRegularMarketDayHigh());
        $quote->setLow($providerQuote->getRegularMarketDayLow());
        $quote->setClose($providerQuote->getRegularMarketPrice());
        $quote->setVolume($providerQuote->getRegularMarketVolume());

        return $quote;
 	}
 
 	/**
 	 * {@inheritDoc}
 	 * @param App\Entity\OHLCVQuote $quote
 	 */
 	public function saveQuote($instrument, $quote)
 	{
	    // if (!in_array($quote['interval'], $this->intervals)) throw new PriceHistoryException(sprintf('Interval `%s` is not supported.'));

 		if ($oldQuote = $instrument->getOHLCVQuote())
 		{
 			// $oldQuote->setTimestamp($quote['timestamp']);
 	  //       $oldQuote->setOpen($quote['open']);
	   //      $oldQuote->setHigh($quote['high']);
	   //      $oldQuote->setLow($quote['low']);
	   //      $oldQuote->setClose($quote['close']);
	   //      $oldQuote->setVolume($quote['volume']);
	   //      $oldQuote->setTimeinterval(new \DateInterval($quote['interval']));
 			$oldQuote->setTimestamp($quote->getTimestamp());
 	        $oldQuote->setOpen($quote->getOpen());
	        $oldQuote->setHigh($quote->getHigh());
	        $oldQuote->setLow($quote->getLow());
	        $oldQuote->setClose($quote->getClose());
	        $oldQuote->setVolume($quote->getVolume());
	        $oldQuote->setTimeinterval($quote->getTimeinterval());
	        $oldQuote->setProvider(self::PROVIDER_NAME);
 		} else {
 		// 	$newQuote = new OHLCVQuote();
	  //       $newQuote->setTimestamp($quote['timestamp']);
	  //       $newQuote->setOpen($quote['open']);
	  //       $newQuote->setHigh($quote['high']);
	  //       $newQuote->setLow($quote['low']);
	  //       $newQuote->setClose($quote['close']);
	  //       $newQuote->setVolume($quote['volume']);
	  //       $newQuote->setTimeinterval(new \DateInterval($quote['interval']));
	  //       $newQuote->setProvider(self::PROVIDER_NAME);
	  //       $newQuote->setInstrument($instrument);

			$instrument->setOHLCVQuote($quote);
 		}
        $this->em->persist($instrument);
        $this->em->flush();
 	}
 
 	/**
 	 * {@inheritDoc}
 	 */
 	public function addQuoteToHistory($quote, $history = [])
 	{
 		if (!empty($history)) {
 			end($history);
 			$lastElement = current($history);
 			$indexOfLastElement = key($history);

 			// check if instruments match
 			if ($lastElement->getInstrument()->getSymbol() != $quote->getInstrument()->getSymbol()) throw new PriceHistoryException('Instrments in history and quote don\'t match');
 			// check if intervals match
 			$historyInterval = $lastElement->getTimeinterval();
 			$quoteInterval = $quote->getTimeinterval();
 			if ($this->convertInterval($historyInterval, 's') != $this->convertInterval($quoteInterval, 's')) throw new PriceHistoryException('Time intervals in history and quote don\'t match');

 			// check if quote date is the same as latest history date, then just overwrite, return $history modified
 			if ($lastElement->getTimestamp()->format('Y-m-d') == $quote->getTimestamp()->format('Y-m-d')) {
 				$lastElement->setTimestamp($quote->getTimestamp());
 				$lastElement->setOpen($quote->getOpen());
 				$lastElement->setHigh($quote->getHigh());
 				$lastElement->setLow($quote->getLow());
 				$lastElement->setClose($quote->getClose());
 				$lastElement->setVolume($quote->getVolume());
 				
 				$history[$indexOfLastElement] = $lastElement;

 				reset($history); // resets array pointer to first element
 				
 				return $history;
 			}
 			// check if latest history date is prevT (previous trading period for weekly, monthly, and yearly) from quote date, then add quote on top of history, return $history modified
 			else {
 				// depending on time interval we must handle the prevT differently
 				switch ($quoteInterval) {
 					case 1 == $quoteInterval->d :
 						$prevT = $this->exchangeEquities->calcPreviousTradingDay($quote->getTimestamp());
 						if ($lastElement->getTimestamp()->format('Y-m-d') == $prevT->format('Y-m-d')) {
				            $lastElement = new OHLCVHistory();
				            $lastElement->setInstrument($quote->getInstrument());
				            $lastElement->setProvider(self::PROVIDER_NAME);
				            $lastElement->setTimestamp($quote->getTimestamp());
				            $lastElement->setTimeinterval($quoteInterval);
			 				$lastElement->setOpen($quote->getOpen());
			 				$lastElement->setHigh($quote->getHigh());
			 				$lastElement->setLow($quote->getLow());
			 				$lastElement->setClose($quote->getClose());
			 				$lastElement->setVolume($quote->getVolume());

			 				$history[] = $lastElement;

			 				reset($history); // resets array pointer to first element
 				
			 				return $history;
 						} 
 					break;
 					case 7 == $quoteInterval->d :

 					break;
 					case 1 == $quoteInterval->m :

 					break;
 					case 1 == $quoteInterval->y :

 					break;
 				}

 				// otherwise you have a gap, return false
 				return false;
 			}
 		} 
 		// // check if there is history in storage. Repeat above logic
 		// elseif () {

 		// } 
 		// if there is no history in storage return null
 		// return null;
 	}

  	/**
 	 * {@inheritDoc}
 	 */
	public function retrieveQuote($instrument)
	{
		return $instrument->getOHLCVQuote();
	}

 	/**
 	 * {@inheritDoc}
 	 */
	public function downloadClosingPrice($instrument) {
 		if ('test' == $_SERVER['APP_ENV'] && isset($_SERVER['TODAY'])) {
			$today = $_SERVER['TODAY'];
		} else {
			$today = date('Y-m-d H:i:s');
		}

		$dateTime = new \DateTime($today);
		if ($this->exchangeEquities->isTradingDay($dateTime)) {
			if (!$this->exchangeEquities->isOpen($dateTime)) {
				// download closing price by getting quote from Yahoo
				$providerQuote = $this->priceProvider->getQuote($instrument->getSymbol());
				$interval = new \DateInterval('P1D');

				if (!($providerQuote instanceof Quote)) throw new PriceHistoryException('Returned provider quote is not instance of Scheb\YahooFinanceApi\Results\Quote');

				$historyItem = new OHLCVHistory();

		        $historyItem->setInstrument($instrument);
		        $historyItem->setProvider(self::PROVIDER_NAME);
		        $historyItem->setTimestamp($providerQuote->getRegularMarketTime());
		        $historyItem->setTimeinterval($interval);
		        $historyItem->setOpen($providerQuote->getRegularMarketOpen());
		        $historyItem->setHigh($providerQuote->getRegularMarketDayHigh());
		        $historyItem->setLow($providerQuote->getRegularMarketDayLow());
		        $historyItem->setClose($providerQuote->getRegularMarketPrice());
		        $historyItem->setVolume($providerQuote->getRegularMarketVolume());

		        return $historyItem;
			} else {
				// it is trading day and market is open
				return null;
			}
		} else {
			$prevT = $this->exchangeEquities->calcPreviousTradingDay($dateTime);
			// get 1 day history for prevT
			$gapHistory = $this->downloadHistory($instrument, $prevT, $dateTime, ['interval' => 'P1D' ]);
			$closingPrice = array_pop($gapHistory);

			$historyItem = new OHLCVHistory();

	        $historyItem->setInstrument($instrument);
	        $historyItem->setProvider(self::PROVIDER_NAME);
	        $historyItem->setTimestamp($providerQuote->getRegularMarketTime());
	        $historyItem->setTimeinterval($interval);
	        $historyItem->setOpen($providerQuote->getRegularMarketOpen());
	        $historyItem->setHigh($providerQuote->getRegularMarketDayHigh());
	        $historyItem->setLow($providerQuote->getRegularMarketDayLow());
	        $historyItem->setClose($providerQuote->getRegularMarketPrice());
	        $historyItem->setVolume($providerQuote->getRegularMarketVolume());

			return $historyItem;			
		}
	}

	public function retrieveClosingPrice($instrument) {}

	public function addClosingPriceToHistory($closingPrice, $history) {}

	private function sortHistory(&$history)
	{
		uasort($history, function($a, $b) {
		    if ($a->getTimestamp()->format('U') == $b->getTimestamp()->format('U')) {
    			return 0;
			}
			return ($a->getTimestamp()->format('U') < $b->getTimestamp()->format('U')) ? -1 : 1;
		});
	}

	/**
	 * Converts DateInterval into a given unit. For now supports only seconds (s)
	 * @param \DateInterval $interval
	 * @param string $unit
	 * @return integer $result
	 */
	private function convertInterval($interval, $unit)
	{
		switch ($unit) {
			case 's':
				$result = 86400 * ($interval->y * 365 + $interval->m * 28.5 + $interval->d) + $interval->h * 3600 + $interval->m * 60 + $interval->s;
 				break;
		}

		return $result;
	}
}
