<?php
/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Service\Exchange\Equities;


use App\Repository\InstrumentRepository;
use App\Service\Exchange\ExchangeInterface;

abstract class Exchange implements ExchangeInterface
{
    /**
     * @var TradingCalendar
     */
    private $tradingCalendar;

    /**
     * @var App\Repository\InstrumentRepository
     */
    protected $instrumentRepository;

    public function __construct(
      InstrumentRepository $instrumentRepository,
      TradingCalendar $tradingCalendar
    )
    {
        $this->tradingCalendar = $tradingCalendar;
        $this->instrumentRepository = $instrumentRepository;
    }

    public function isTradingDay($date) {
        $this->tradingCalendar->getInnerIterator()->setStartDate($date);
//        $this->tradingCalendar->getInnerIterator()->rewind();
//        $value = $this->tradingCalendar->current();
//        if ($value == $date) {
//            return true;
//        }

        $limitIterator = new \LimitIterator($this->tradingCalendar, 0, 1);
        foreach ($limitIterator as $key => $value) {
            if ($value == $date) {
                return true;
            }
        }

        return false;
    }

    abstract function isOpen($dateTime);

    public function getTradedInstruments()
    {
        return ($this->instrumentRepository->findBy(['exchange' => static::getExchangeName()]));
    }

    public function isTraded($symbol)
    {
        return ($this->instrumentRepository->findOneBy(['symbol' => $symbol, 'exchange' => static::getExchangeName()]))? true : false;
    }

    public function calcPreviousTradingDay($date)
    {
        $this->tradingCalendar->getInnerIterator()->setStartDate($date)->setDirection(-1);
        $this->tradingCalendar->getInnerIterator()->rewind();
        $this->tradingCalendar->next();
        $value = $this->tradingCalendar->current();

        return $value;
    }

    public function calcNextTradingDay($date)
    {
        $this->tradingCalendar->getInnerIterator()->setStartDate($date)->setDirection(1);
        $this->tradingCalendar->getInnerIterator()->rewind();
        $this->tradingCalendar->next();
        $value = $this->tradingCalendar->current();

        return $value;
    }

    public function getTradingCalendar()
    {
        return $this->tradingCalendar;
    }
}