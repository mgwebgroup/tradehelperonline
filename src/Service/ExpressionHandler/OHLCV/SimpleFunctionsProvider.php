<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\ExpressionHandler\OHLCV;

use App\Exception\PriceHistoryException;
use App\Service\Exchange\MonthlyIterator;
use App\Service\Exchange\WeeklyIterator;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use App\Service\Exchange\Catalog;

class SimpleFunctionsProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \App\Service\Exchange\Catalog
     */
    private $catalog;

    public function __construct(
        EntityManager $em,
        Catalog $catalog
    )
    {
        $this->em = $em;
        $this->catalog = $catalog;
    }

    public function getFunctions()
    {
        return [
            new ExpressionFunction(
                'Close',
                function ($offset) { return null; },
                function($arguments, $offset) { return $this->getValue('close', $offset, $arguments); }
                ),
            new ExpressionFunction(
                'Open',
                function ($offset) { return null; },
                function($arguments, $offset) { return $this->getValue('open', $offset, $arguments); }
                ),
            new ExpressionFunction(
                'High',
                function ($offset) { return null; },
                function($arguments, $offset) { return $this->getValue('high', $offset, $arguments); }
            ),
            new ExpressionFunction(
                'Low',
                function ($offset) { return null; },
                function($arguments, $offset) { return $this->getValue('low', $offset, $arguments); }
            ),
            new ExpressionFunction(
                'Volume',
                function ($offset) { return null; },
                function($arguments, $offset) { return $this->getValue('volume', $offset, $arguments); }
            ),
            new ExpressionFunction(
            'Avg',
            function ($column, $period) { return null; },
            function($arguments, $column, $period) { return $this->getAverage($column, $period, $arguments); }
            ),
            ];
    }

    /**
     * @param sting $column name in ohlcvhistory table
     * @param integer $offset
     * @param array $arguments
     * @return mixed
     * @throws PriceHistoryException
     */
    protected function getValue($column, $offset, $arguments)
    {
        $column = strtolower($column);
        try {
            $instrument = $this->getInstrument($arguments);
            $interval = $this->getInterval($arguments);
            $today = $this->getToday($arguments);
            $exchange = $this->catalog->getExchangeFor($instrument);
            $tradingCalendar = $exchange->getTradingCalendar();
            $tradingCalendar->getInnerIterator()->setStartDate($today)->setDirection(-1);

            $offsetDate = $this->figureOffsetDate($tradingCalendar, $interval, $offset);

            $dql = sprintf('select h.%s from \App\Entity\OHLCVHistory h
                join h.instrument i
                where i.id =  :id and
                date_format(h.timestamp, \'%%Y-%%m-%%d\') = :date and
                h.timeinterval = :interval',
                           $column);

            $query = $this->em->createQuery($dql);
            $query->setParameter('id', $instrument->getId());
            $query->setParameter('date', $offsetDate->format('Y-m-d'));
            $query->setParameter('interval', $interval);

            // to ignore dates use a limit statement
//            $dql = sprintf('select h.%s from \App\Entity\OHLCVHistory h
//                join h.instrument i
//                where i.id = :id and
//                date_format(h.timestamp, \'%%Y-%%m-%%d\') <= :date and
//                h.timeinterval = :interval
//                order by h.timestamp desc',
//                           $column);
//            $query = $this->em->createQuery($dql)->setFirstResult($offset)->setMaxResults(1);
//            $query->setParameter('id', $instrument->getId());
//            $query->setParameter('date', $today->format('Y-m-d'));
//            $query->setParameter('interval', $interval);

            $result = $query->getArrayResult();
            if (empty($result)) {
                throw new NoResultException();
            }
            return $result[0][$column];
        } catch (NoResultException $e) {
            throw new PriceHistoryException(sprintf('Could not find value for `Close(%d)`', $offset), 0);
        }
    }

    /**
     * @param sting $column name in ohlcvhistory table
     * @param integer $offset
     * @param array $arguments
     * @return mixed
     * @throws PriceHistoryException
     */
    protected function getAverage($column, $period, $arguments) {
        $column = strtolower($column);
        try {
            $instrument = $this->getInstrument($arguments);
            $interval = $this->getInterval($arguments);
            $today = $this->getToday($arguments);
            $exchange = $this->catalog->getExchangeFor($instrument);
            $tradingCalendar = $exchange->getTradingCalendar();
            $tradingCalendar->getInnerIterator()->setStartDate($today)->setDirection(-1);

            $offsetDate = $this->figureOffsetDate($tradingCalendar, $interval, $period);

            // not using avg(h.%s) aggregate function, because it will mask error if number of records present is less
            // than $period
            $dql = sprintf('select h.%s from \App\Entity\OHLCVHistory h
                join h.instrument i
                where i.id = :id and
                date_format(h.timestamp, \'%%Y-%%m-%%d\') > :date and
                h.timeinterval = :interval
                order by h.timestamp desc',
                           $column);
            $query = $this->em->createQuery($dql);
            $query->setParameter('id', $instrument->getId());
            $query->setParameter('date', $offsetDate->format('Y-m-d'));
            $query->setParameter('interval', $interval);

            // to ignore dates use limit statement:
//            $dql = sprintf('select h.%s from \App\Entity\OHLCVHistory h
//                join h.instrument i
//                where i.id = :id and
//                date_format(h.timestamp, \'%%Y-%%m-%%d\') <= :date and
//                h.timeinterval = :interval
//                order by h.timestamp desc',
//                           $column);
//            $query = $this->em->createQuery($dql)->setMaxResults($period);
//            $query->setParameter('id', $instrument->getId());
//            $query->setParameter('date', $today->format('Y-m-d'));
//            $query->setParameter('interval', $interval);

            $result = $query->getResult();

            if (count($result) < $period) {
                throw new PriceHistoryException(sprintf('Not enough values for `Average(%s, %d)`', $column, $period), 3);
            } elseif (count($result) > $period) {
                throw new PriceHistoryException(sprintf('Error in getting accurate result for `Average(%s, %d)`',
                                                        $column, $period), 2);
            }

            return array_sum(array_map(function($v) use ($column)  {return $v[$column];}, $result)) / count($result);
        } catch (NoResultException $e) {
            throw new PriceHistoryException(sprintf('Could not find value for `Average(%s, %d)`', $column, $period), 1);
        }
    }

    /**
     * @param array $arguments
     * @return \App\Entity\Instrument
     * @throws SyntaxError
     */
    private function getInstrument($arguments)
    {
        if (isset($arguments['instrument']) && $arguments['instrument'] instanceof \App\Entity\Instrument) {
            $instrument = $arguments['instrument'];
        } else {
            throw new SyntaxError('Need to pass instrument object as part of the data part');
        }

        return $instrument;
    }

    /**
     * @param array $arguments
     * @return string
     * @throws \Exception
     */
    private function getInterval($arguments)
    {
        if (isset($arguments['interval']) && $arguments['interval'] instanceof \DateInterval) {
            $interval = $arguments['interval']->format('%RP%YY%MM%DDT%HH%IM%SS');
        } else {
            $defaultInterval = new \DateInterval('P1D');
            $interval = $defaultInterval->format('%RP%YY%MM%DDT%HH%IM%SS');
        }

        return $interval;
    }

    /**
     * @param array $arguments
     * @return \DateTime
     * @throws \Exception
     */
    private function getToday($arguments) {
        if ('test' == $_SERVER['APP_ENV'] && isset($_SERVER['TODAY'])) {
            $today = new \DateTime($_SERVER['TODAY']);
        } else {
            $today = new \DateTime();
        }

        if (isset($arguments['date']) && $arguments['date'] instanceof \DateTime) {
            $today = $arguments['date'];
        }

        return $today;
    }

    /**
     * Figures the date from which to retrieve price records. This date is based on the offset given as argument to
     * ths scanner function, i.e. Close(10)
     * @param App\Service\Exchange\Equities\TradingCalendar $tradingCalendar
     * @param integer $interval
     * @param integer $offset
     * @return \DateTime
     * @throws \Exception
     */
    private function figureOffsetDate($tradingCalendar, $interval, $offset)
    {
        if ('+P00Y00M01DT00H00M00S' == $interval) {
            $limitIterator = new \LimitIterator($tradingCalendar, $offset, 1);
        } elseif ('+P00Y00M07DT00H00M00S' == $interval) {
            $weeklyIterator = new WeeklyIterator($tradingCalendar);
            $limitIterator = new \LimitIterator($weeklyIterator, $offset, 1);
        } elseif ('+P00Y01M00DT00H00M00S' == $interval) {
            $monthlyIterator = new MonthlyIterator($tradingCalendar);
            $limitIterator = new \LimitIterator($monthlyIterator, $offset, 1);
        } else {
            throw new SyntaxError(sprintf('Undefined interval %s', $interval));
        }
        $limitIterator->rewind();

        return $limitIterator->current();
    }
}