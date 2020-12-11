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
 * Class WeeklyIterator
 * Alters $date property of Daily Iterator (using trade day filter set through Trading Calendar) and sets it to
 * beginning of the week. Beginning of the week is either a Monday, or Tuesday if Monday is a holiday.
 * @package App\Service\Exchange
 */
class WeeklyIterator implements OuterIterator
{
    /**
     * @var TradingCalendar
     */
    protected $innerIterator;

    /**
     * @var DateInterval
     */
    protected $weekInterval;


    public function __construct(TradingCalendar $iterator)
    {
        $this->innerIterator = $iterator;
        $this->weekInterval = new DateInterval('P7D');
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
        if ($date->format('N') != 1) {
            if ($this->getInnerIterator()->getInnerIterator()->getDirection() < 1) {
                $date->sub($this->weekInterval);
            } else {
                $date->add($this->weekInterval);
            }
            $date->modify('last Monday');
        } else {
            if ($this->getInnerIterator()->getInnerIterator()->getDirection() < 1) {
                $date->sub($this->weekInterval);
            } else {
                $date->add($this->weekInterval);
            }
        }
        if (false === $this->getInnerIterator()->accept()) {
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
     * Rewinds to Monday of the week for which date is set in DailyIterator.
     * Rewinds to immediate Tuesday if StartDate is a Monday Holiday
     */
    public function rewind()
    {
        $this->getInnerIterator()->getInnerIterator()->rewind();
        $date = $this->getInnerIterator()->getInnerIterator()->current();
        if ($date->format('N') != 1) {
            $date->modify('last Monday');
        }
        if (false === $this->getInnerIterator()->accept()) {
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
