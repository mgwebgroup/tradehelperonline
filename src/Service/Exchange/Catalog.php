<?php
/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Service\Exchange;


use App\Service\Exchange\Equities\NASDAQ;
use App\Service\Exchange\Equities\NYSE;
use App\Repository\InstrumentRepository;
use App\Service\Exchange\Equities\TradingCalendar;

class Catalog implements \ArrayAccess
{
    /**
     * @var App\Service\Exchange\ExchangeInterface[]
     */
    protected $exchanges;

    public function __construct(
      InstrumentRepository $instrumentRepository,
      TradingCalendar $tradingCalendar
    )
    {
        $this->exchanges = [
            'NASDAQ' => new NASDAQ($instrumentRepository, $tradingCalendar),
            'NYSE' => new NYSE($instrumentRepository, $tradingCalendar)
        ];
    }

    public function offsetExists($offset)
    {
        return isset($this->exchanges[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->exchanges[$offset]) ? $this->exchanges[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->exchanges[] = $value;
        } else {
            $this->exchanges[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->exchanges[$offset]);
    }

    public function getExchangeFor($instrument)
    {
        return $this->offsetGet($instrument->getExchange());
    }
}