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
use App\Exception\PriceHistoryException;
use App\Entity\OHLCVQuote;
use App\Entity\Instrument;
use Scheb\YahooFinanceApi\Results\Quote;


/**
 * Class Yahoo
 * Works with OLCV format price histories and quotes.
 * Downloads from Yahoo unadvertised API
 * To configure a different price provider use service configuration file to specify factory and method.
 * @uses \Scheb\YahooFinanceApi\ApiClient
 */
class Yahoo implements \App\Service\PriceHistory\PriceProviderInterface
{
	const PROVIDER_NAME = 'YAHOO';

	/**
	* Currently supported intervals from the Price Provider 
	* These follow interval spec for the \DateInterval class
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

	public $em;

	private $instrumentRepository;

	public function __construct(
        \Symfony\Bridge\Doctrine\RegistryInterface $registry,
        \Scheb\YahooFinanceApi\ApiClient $priceProvider
    ) {
        $this->em = $registry->getManager();
        $this->priceProvider = $priceProvider;
        $this->instrumentRepository = $this->em->getRepository(Instrument::class);
	}

    /**
     * {@inheritDoc}
     * @param $instrument
     * @param $fromDate
     * @param $toDate
     * @param array $options ['interval' => 'P1M|P1W|P1D' ]
     * @return \App\Entity\OHLCVHistory[]
     * @throws PriceHistoryException
     * @throws \Scheb\YahooFinanceApi\Exception\ApiException
     */
	public function downloadHistory($instrument, $fromDate, $toDate, $options)
	{
		if ('test' == $_SERVER['APP_ENV'] && isset($_SERVER['TODAY'])) {
			$today = $_SERVER['TODAY'];
		} else {
			$today = date('Y-m-d');
		}

        $exchange = $this->getExchangeForInstrument($instrument);

		if ($toDate->format('U') > strtotime($today)) {
			$toDate = new \DateTime($today);
		}
		// test for exceptions:
		if ($toDate->format('Y-m-d') == $fromDate->format('Y-m-d')) {
			throw new PriceHistoryException(sprintf('$fromDate %s is equal to $toDate %s', $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')));
		}
		if ($toDate->format('U') < $fromDate->format('U')) {
			throw new PriceHistoryException(sprintf('$toDate %s is earlier than $fromDate %s', $toDate->format('Y-m-d'), $fromDate->format('Y-m-d')));	
		}
        $hours = ($toDate->format('U') - $fromDate->format('U')) / 3600;
		// check if $toDate and $fromDate are on the same weekend, then except if ()
		if ($fromDate->format('w') == 6 && $toDate->format('w') == 0 && $hours <= 48 ) {
			throw new PriceHistoryException(sprintf('$fromDate %s and $toDate %s are on the same weekend.', $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')));
		}
		// check if $toDate or $fromDate is a long weekend (includes a contiguous holiday, then except
		if ($hours <= 72 && ((!$exchange->isTradingDay($fromDate) && $toDate->format('w') == 0) || ($fromDate->format('w') == 6 && !$exchange->isTradingDay($toDate)) )) {
			throw new PriceHistoryException(sprintf('$fromDate %s and $toDate %s are on the same long weekend.', $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')));
		}


		if (isset($options['interval']) && in_array($options['interval'], $this->intervals)) {
			switch ($options['interval']) {
				case 'P1M':
					$apiInterval = $this->priceProvider::INTERVAL_1_MONTH;
					$interval = new \DateInterval('P1M');
					break;
				case 'P1W':
					$apiInterval = $this->priceProvider::INTERVAL_1_WEEK;
					$interval = new \DateInterval('P1W');
					break;
				case 'P1D':
                    $apiInterval = $this->priceProvider::INTERVAL_1_DAY;
                    $interval = new \DateInterval('P1D');
                    break;
                default:
                    throw new PriceHistoryException(sprintf('Interval %s is not serviced', $options['interval']));
			}
		} else {
//			$apiInterval = $this->priceProvider::INTERVAL_1_DAY;
            throw new PriceHistoryException(sprintf('Interval %s is not serviced', $options['interval']));
		}

		$history = $this->priceProvider->getHistoricalData($instrument->getSymbol(), $apiInterval, $fromDate, $toDate);
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

	public function addHistory($instrument, $history)
	{
		if (!empty($history)) {
			// delete existing OHLCV History for the given instrument from history start date to current date
			$OHLCVRepository = $this->em->getRepository(OHLCVHistory::class);
			// var_dump(get_class($OHLCVRepository)); exit();
			$fromDate = $history[0]->getTimestamp();
			$interval = $history[0]->getTimeinterval();
			$OHLCVRepository->deleteHistory($instrument, $fromDate, null, $interval, self::PROVIDER_NAME);

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
 	    // TO DO: implement export of history to file system
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

 		return $OHLCVRepository->retrieveHistory($instrument, $interval, $fromDate, $toDate, self::PROVIDER_NAME);
 	}

 	public function downloadQuote($instrument) {
 		if ('test' == $_SERVER['APP_ENV'] && isset($_SERVER['TODAY'])) {
			$today = $_SERVER['TODAY'];
		} else {
			$today = date('Y-m-d H:i:s');
		}

		$dateTime = new \DateTime($today);
		// var_dump($dateTime); exit();

        $exchange = $this->getExchangeForInstrument($instrument);
		if (!$exchange->isOpen($dateTime)) return null;

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
     * @param $quote App\Entity\OHLCVQuote
     * @param $history App\Entity\OHLCVHistory[]
     * {@inheritDoc}
     * @throws PriceHistoryException
     */
 	public function addQuoteToHistory($quote, $history = [])
 	{
        // handle test environment
        if ('test' == $_SERVER['APP_ENV'] && isset($_SERVER['TODAY'])) {
            $today = new \DateTime($_SERVER['TODAY']);
        } else {
            $today = new \DateTime();
        }

        if ($today->format('Ymd') != $quote->getTimestamp()->format('Ymd')) {
            return null;
        }

        $instrument = $quote->getInstrument();
        $exchange = $this->getExchangeForInstrument($instrument);
        $quoteInterval = $quote->getTimeinterval();
        $prevT = $exchange->calcPreviousTradingDay($quote->getTimestamp());

        if ($exchange->isOpen($today)) {
            if (empty($history)) {
                // is there history in storage?
                $repository = $this->em->getRepository(OHLCVHistory::class);
                $history = $repository->retrieveHistory($instrument, $quoteInterval, $prevT, $today, self::PROVIDER_NAME);
                if (!empty($history)) {
                    // check if quote date is the same as latest history date, then just overwrite and save in db, return true
                    $lastElement = array_pop($history);
                    if ($lastElement->getTimestamp()->format('Ymd') == $quote->getTimestamp()->format('Ymd')) {
                        $this->setHistoryElementToQuote($lastElement, $quote);

                        $this->em->persist($lastElement);
                        $this->em->flush();

                        return true;
                    }
                    // check if latest history date is prevT (previous trading period for weekly, monthly, and yearly) from quote date, then add quote on top of history, return true
                    else {
                        switch ($quoteInterval) {
                            case 1 == $quoteInterval->d :
                                if ($lastElement->getTimestamp()->format('Ymd') == $prevT->format('Ymd')) {
                                    $newElement = new OHLCVHistory();
                                    $newElement->setInstrument($instrument);
                                    $newElement->setProvider(self::PROVIDER_NAME);
                                    $newElement->setTimeinterval($quoteInterval);
                                    $this->setHistoryElementToQuote($newElement, $quote);

                                    $this->em->persist($newElement);
                                    $this->em->flush();

                                    return true;
                                }
                            case 7 == $quoteInterval->d :

                                break;
                            case 1 == $quoteInterval->m :

                                break;
                            case 1 == $quoteInterval->y :
                        }
                        // quote must be inside of history
                        return false;
                    }
                }
                // decide if need to return false (gap) or null (no op). Check if history exists for an instrument
                else {
                    $history = $repository->retrieveHistory($instrument, $quoteInterval, null, $today, self::PROVIDER_NAME);
                    if (empty($history)) {
                        return null;
                    } else {
                        return false;
                    }
                }
            } else {
                end($history);
                $lastElement = current($history);
                $indexOfLastElement = key($history);

                // check if instruments match
                if ($lastElement->getInstrument()->getSymbol() != $quote->getInstrument()->getSymbol()) {
                    throw new PriceHistoryException('Instruments in history and quote don\'t match');
                }
                // check if intervals match
                $historyInterval = $lastElement->getTimeinterval();

                if ($historyInterval->format('ymdhis') != $quoteInterval->format('ymdhis')) {
                    throw new PriceHistoryException('Time intervals in history and quote don\'t match');
                }

                // check if quote date is the same as latest history date, then just overwrite, return $history modified
                if ($lastElement->getTimestamp()->format('Ymd') == $quote->getTimestamp()->format('Ymd')) {
                    $this->setHistoryElementToQuote($lastElement, $quote);

                    $history[$indexOfLastElement] = $lastElement;
                    reset($history); // resets array pointer to first element

                    return $history;
                }
                // check if latest history date is prevT (previous trading period for weekly, monthly, and yearly) from quote date, then add quote on top of history, return $history modified
                else {
                    // depending on time interval we must handle the prevT differently
                    switch ($quoteInterval) {
                        case 1 == $quoteInterval->d :
                            if ($lastElement->getTimestamp()->format('Ymd') == $prevT->format('Ymd')) {
                                $lastElement = new OHLCVHistory();
                                $lastElement->setInstrument($instrument);
                                $lastElement->setProvider(self::PROVIDER_NAME);
                                $lastElement->setTimeinterval($quoteInterval);

                                $this->setHistoryElementToQuote($lastElement, $quote);

                                $history[] = $lastElement;

                                reset($history); // resets array pointer to first element

                                return $history;
                            }
                        case 7 == $quoteInterval->d :

                            break;
                        case 1 == $quoteInterval->m :

                            break;
                        case 1 == $quoteInterval->y :
                    }
                    return false;
                }
            }
        }
        return null;
 	}

	public function retrieveQuote($instrument)
	{
		return $instrument->getOHLCVQuote();
	}

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

    /**
     * @param App\Entity\Instrument $instrument
     * @return App\Service\Exchange\ExchangeInterface $exchange
     * @throws PriceHistoryException
     */
    private function getExchangeForInstrument($instrument) {
        $exchangeClassName = '\App\Service\Exchange\\' . $instrument->getExchange();
        if (!class_exists($exchangeClassName)) {
            throw new PriceHistoryException(sprintf('Class for exchange name %s not defined', $exchangeClassName));
        }
        return new $exchangeClassName($this->instrumentRepository);
    }

    private function setHistoryElementToQuote($element, $quote)
    {
        $element->setTimestamp($quote->getTimestamp());
        $element->setOpen($quote->getOpen());
        $element->setHigh($quote->getHigh());
        $element->setLow($quote->getLow());
        $element->setClose($quote->getClose());
        $element->setVolume($quote->getVolume());
    }
}
