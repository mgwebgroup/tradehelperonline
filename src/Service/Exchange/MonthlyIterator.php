<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Exchange;

use App\Service\Exchange\Equities\TradingCalendar;
use OuterIterator;
use DateInterval;

/**
 * Class MonthlyIterator
 * Plug dates into seek and receive either a date for the Monday of the week, or next day if Monday is a holiday
 * @package App\Service\Exchange
 */
class MonthlyIterator implements OuterIterator
{
    /**
     * @var TradingCalendar
     */
    protected $innerIterator;

    /**
     * @var DateInterval
     */
    protected $monthInterval;


    public function __construct(TradingCalendar $iterator)
    {
        $this->innerIterator = $iterator;
        $this->monthInterval = new DateInterval('P1M');
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->getInnerIterator()->getInnerIterator()->current();
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        $date = $this->getInnerIterator()->getInnerIterator()->current();
        if ($date->format('j') != 1) {
            if ($this->getInnerIterator()->getInnerIterator()->getDirection() < 1) {
                $date->sub($this->monthInterval);
            } else {
                $date->add($this->monthInterval);
            }
            $date->modify(sprintf('first day of %s %s', $date->format('F'), $date->format('Y')));
        } else {
            if ($this->getInnerIterator()->getInnerIterator()->getDirection() < 1) {
                $date->sub($this->monthInterval);
            } else {
                $date->add($this->monthInterval);
            }
        }
        while (false === $this->getInnerIterator()->accept()) {
            $date->add(new \DateInterval(DailyIterator::INTERVAL));
        }
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->getInnerIterator()->getInnerIterator()->key();
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        return $this->getInnerIterator()->getInnerIterator()->valid();
    }

    /**
     * Sets $date property in DailyIterator to first working day of the month. This is regardless of which
     * direction is set.
     */
    public function rewind()
    {

        $this->getInnerIterator()->getInnerIterator()->rewind();
        $date = $this->getInnerIterator()->getInnerIterator()->current();
        if ($date->format('j') != 1) {
            $date->modify(sprintf('first day of %s %s', $date->format('F'), $date->format('Y')));
        }
        while (false === $this->getInnerIterator()->accept()) {
            $date->add(new \DateInterval(DailyIterator::INTERVAL));
        }
    }

    /**
     * @inheritDoc
     */
    public function getInnerIterator()
    {
        return $this->innerIterator;
    }
}
