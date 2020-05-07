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

use DateTime;
use Yasumi\Yasumi;

/**
 * Class WeeklyIterator
 * Simple iterator to iterate between START and END dates in both directions
 * @package App\Service\Exchange
 */
class WeeklyIterator implements \Iterator
{
    const INTERVAL = 'P7D';
    const WEEKDAY_START = 1;
    const TIMEZONE = 'America/New_York';

    /**
     * '2000-01-01'
     */
    const START = 946684800;

    /**
     * '2100-12-31'
     */
    const END = 4133894400;

    /**
     * UNIX Timestamp
     * @var int
     */
    protected $lowerLimit;

    /**
     * UNIX Timestamp
     * @var int
     */
    protected $upperLimit;

    /** @var DateTime */
    protected $date;

    /**
     * Stores start date for the rewind method
     * @var DateTime
     */
    protected $startDate;

    /** @var integer */
    protected $direction;

    /**
     * @var integer
     */
    protected $startingWeekday;

    /**
     * @var Yasumi\Provider\USA
     */
    protected $holidaysCalculator;

    public function __construct($start = null, $end = null, $startingWeekday = 1)
    {
        $this->lowerLimit = (is_numeric($start) && $start > 0)? $start : self::START;
        $this->lowerLimit = $this->findBeginningDate(new DateTime('@'.$this->lowerLimit))->getTimestamp();
        $this->upperLimit = (is_numeric($end) && $end > 0)? $end : self::END;
        $this->upperLimit = $this->findBeginningDate(new DateTime('@'.$this->upperLimit))->getTimestamp();
        $this->direction = 1;
        $this->startingWeekday = (is_int($startingWeekday) && $startingWeekday > 0 && $startingWeekday < 8 )?
          $startingWeekday : self::WEEKDAY_START;
    }
    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->date;
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        if ($this->direction > 0) {
            $this->date->add(new \DateInterval(self::INTERVAL));
        } else {
            $this->date->sub(new \DateInterval(self::INTERVAL));
        }

        $this->findBeginningDate($this->date);
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->date->format('Ymd');
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        if ($this->date instanceof DateTime) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        if ($this->startDate === null) {
            if ($this->direction > 0) {
                $this->date = new DateTime('@'.$this->lowerLimit);
            } else {
                $this->date = new DateTime('@'.$this->upperLimit);
            }
        } else {
            $this->date = clone $this->startDate;
        }

    }

    /**
     * @param integer $direction
     * @throws Exception
     */
    public function setDirection($direction)
    {
        if (is_numeric($direction)) {
            if ($direction > 0) {
                $this->direction = 1;
            } else {
                $this->direction = -1;
            }
        } else {
            throw new \Exception('Value of direction must be numeric');
        }

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param DateTime $date
     */
    public function setStartDate($date)
    {
        if ($date instanceof DateTime) {
            if ($date->getTimestamp() < $this->lowerLimit) {
                throw new Exception(sprintf('Date is older than %s', date($this->lowerLimit, 'c')));
            }
            if ($date->getTimestamp() > $this->upperLimit) {
                throw new \Exception(sprintf('Date is newer that %s', date($this->upperLimit, 'c')));
            }
            $dateClone = clone $date;
            $this->startDate = $this->findBeginningDate($dateClone);
        } else {
            throw new \Exception('Date must be instance of \DateTime');
        }

        return $this;
    }

    public function findBeginningDate($date) {
        $date = $this->findClosest($date);
        while (false === $this->accept($date)) {
            $date->add(new \DateInterval('P1D'));
        }
        return $date;
    }

    public function findClosest($date)
    {
        return ($date->format('N') == $this->startingWeekday)? $date : $date->modify('last Monday');
    }

    public function accept($date)
    {
        $this->initCalculator((int) $date->format('Y'));

        return $this->holidaysCalculator->isWorkingDay($date);
    }

    /**
     * @param integer $year
     * @throws \ReflectionException
     */
    private function initCalculator($year)
    {
        $this->holidaysCalculator = Yasumi::create('USA', $year);

        $this->holidaysCalculator->addHoliday($this->holidaysCalculator->goodFriday($year, self::TIMEZONE, 'en_US'));
        $this->holidaysCalculator->removeHoliday('columbusDay');
        $this->holidaysCalculator->removeHoliday('veteransDay');
    }
}
