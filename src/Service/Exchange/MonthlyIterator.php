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
use SeekableIterator;
use DateInterval;

/**
 * Class MonthlyIterator
 * Plug dates into seek and receive either a date for the Monday of the week, or next day if Monday is a holiday
 * @package App\Service\Exchange
 */
class MonthlyIterator implements SeekableIterator, OuterIterator
{
    /**
     * @var TradingCalendar
     */
    protected $innerIterator;

    /**
     * @var \DateInterval
     */
    protected $interval;


    public function __construct(TradingCalendar $iterator)
    {
        $this->innerIterator = $iterator;

        $this->interval = new \DateInterval('P1M');
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->getInnerIterator()->current();
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        $date = $this->getInnerIterator()->current();
        if ($this->getInnerIterator()->getInnerIterator()->getDirection() > 0) {
            $date->add($this->interval);
        } else {
            $date->sub($this->interval);
        }
        return $this->toBeginning($date);
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
        return $this->getInnerIterator()->valid();
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->getInnerIterator()->rewind();
        $date = $this->getInnerIterator()->current();
        $this->toBeginning($date);
    }

    /**
     * Determines date for beginning of the month. Usually it is date on a weekday, however takes into account holidays.
     * @param \DateTime $date
     * @return \DateTime
     * @throws \Exception
     */
    public function toBeginning($date)
    {
        $date->modify(sprintf('first day of %s %s', $date->format('F'), $date->format('Y')));
        while (false === $this->getInnerIterator()->accept($date)) {
            $date->add(new DateInterval('P1D'));
        }

        return $date;
    }

    public function seek($position)
    {
        $this->getInnerIterator()->rewind();
        $date = $this->getInnerIterator()->current();
        $intervalString = sprintf('P%dM', $position);
        if ($this->getInnerIterator()->getInnerIterator()->getDirection() > 1) {
            $date->add(new \DateInterval($intervalString));
        } else {
            $date->sub(new \DateInterval($intervalString));
        }
        $this->toBeginning($date);
    }

    /**
     * @inheritDoc
     */
    public function getInnerIterator()
    {
        return $this->innerIterator;
    }
}
