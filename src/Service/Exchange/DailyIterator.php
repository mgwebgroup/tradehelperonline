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

use DateInterval;
use DateTime;
use Exception;
use Iterator;

/**
 * Class DailyIterator
 * Simple iterator to iterate between START and END dates in both directions
 * @package App\Service\Exchange
 */
class DailyIterator implements Iterator
{
    public const INTERVAL = 'P1D';
    /**
     * '2000-01-01'
     */
    public const START = 946684800;

    /**
     * '2100-12-31'
     */
    public const END = 4133894400;

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

    public function __construct($start = null, $end = null)
    {
        $this->lowerLimit = (is_int($start) && $start > 0) ? $start : self::START;
        $this->upperLimit = (is_int($end) && $end > 0) ? $end : self::END;
        $this->direction = 1;
    }
    /**
     * @inheritDoc
     */
    public function current(): DateTime
    {
        return $this->date;
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        if ($this->direction > 0) {
            $this->date->add(new DateInterval(self::INTERVAL));
        } else {
            $this->date->sub(new DateInterval(self::INTERVAL));
        }
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
    public function valid(): bool
    {
        if ($this->date instanceof DateTime) {
            return $this->checkBounds($this->date);
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
                $this->date = new DateTime('@' . $this->lowerLimit);
            } else {
                $this->date = new DateTime('@' . $this->upperLimit);
            }
        } else {
            $this->date = clone $this->startDate;
        }
    }

    /**
     * @param integer $direction
     * @return DailyIterator
     * @throws Exception
     */
    public function setDirection(int $direction): DailyIterator
    {
        if (is_numeric($direction)) {
            if ($direction > 0) {
                $this->direction = 1;
            } else {
                $this->direction = -1;
            }
        } else {
            throw new Exception('Value of direction must be numeric');
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getDirection(): int
    {
        return $this->direction;
    }

    /**
     * @param DateTime $date
     * @return DailyIterator
     * @throws Exception
     */
    public function setStartDate(DateTime $date): DailyIterator
    {
        if ($date instanceof DateTime) {
            $this->checkBounds($date);
            $this->startDate = $date;
        } else {
            throw new Exception('Date must be instance of \DateTime');
        }

        return $this;
    }

    private function checkBounds($date): bool
    {
        if ($date->getTimestamp() < $this->lowerLimit) {
            throw new Exception(
                sprintf('Date is below (older than) lower boundary of %s', date('c', $this->lowerLimit))
            );
        }
        if ($date->getTimestamp() > $this->upperLimit) {
            throw new Exception(
                sprintf('Date is above (newer than) upper boundary of %s', date('c', $this->upperLimit))
            );
        }

        return true;
    }
}
