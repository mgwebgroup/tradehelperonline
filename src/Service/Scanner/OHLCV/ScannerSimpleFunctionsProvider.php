<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Scanner\OHLCV;

use App\Exception\PriceHistoryException;
use App\Service\Exchange\MonthlyIterator;
use App\Service\Exchange\WeeklyIterator;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use App\Service\Exchange\Catalog;

class ScannerSimpleFunctionsProvider implements ExpressionFunctionProviderInterface
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
                function($arguments, $offset) { return $this->getValue('close', $arguments, $offset); }
                ),
            new ExpressionFunction(
                'Open',
                function ($offset) { return null; },
                function($arguments, $offset) { return $this->getValue('open', $arguments, $offset); }
                ),
            new ExpressionFunction(
                'High',
                function ($offset) { return null; },
                function($arguments, $offset) { return $this->getValue('high', $arguments, $offset); }
            ),
            new ExpressionFunction(
                'Low',
                function ($offset) { return null; },
                function($arguments, $offset) { return $this->getValue('low', $arguments, $offset); }
            ),
            new ExpressionFunction(
                'Volume',
                function ($offset) { return null; },
                function($arguments, $offset) { return $this->getValue('volume', $arguments, $offset); }
            ),
            new ExpressionFunction(
            'Average',
            function ($column, $period) { return null; },
            function($arguments, $column, $period) { return $this->getAverage($arguments, $column, $period); }
            ),
            ];
    }

    protected function getValue($column, $arguments, $offset)
    {
        $column = strtolower($column);
        try {
            if (isset($arguments['instrument']) && $arguments['instrument'] instanceof \App\Entity\Instrument) {
                $instrument = $arguments['instrument'];
            } else {
                throw new SyntaxError('Need to pass instrument object as part of the data part');
            }
            $interval = null;
            if (isset($arguments['interval']) && $arguments['interval'] instanceof \DateInterval) {
                $interval = $arguments['interval']->format('%RP%YY%MM%DDT%HH%IM%SS');
            } else {
                $defaultInterval = new \DateInterval('P1D');
                $interval = $defaultInterval->format('%RP%YY%MM%DDT%HH%IM%SS');
            }

            if ('test' == $_SERVER['APP_ENV'] && isset($_SERVER['TODAY'])) {
                $today = new \DateTime($_SERVER['TODAY']);
            } else {
                $today = new \DateTime();
            }

            if (isset($arguments['date']) && $arguments['date'] instanceof \DateTime) {
                $today = $arguments['date'];
            }

            $exchange = $this->catalog->getExchangeFor($instrument);
            $tradingCalendar = $exchange->getTradingCalendar();
            $tradingCalendar->getInnerIterator()->setStartDate($today)->setDirection(-1);

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
            $date = $limitIterator->current();

            $dql = sprintf('select h.%s from \App\Entity\OHLCVHistory h join h.instrument i where i.id =  %s and date_format(h.timestamp, \'%%Y-%%m-%%d\') = \'%s\'',
                           $column, $instrument->getId(), $date->format('Y-m-d'));
            if ($interval) {
                $dql .= sprintf(' and h.timeinterval = \'%s\'', $interval);
            }

            $query = $this->em->createQuery($dql);
            $result = $query->getSingleResult();

            return $result[$column];
        } catch (NoResultException $e) {
            throw new PriceHistoryException(sprintf('Could not find value for `Close(%d)`', $offset));
        }
    }

    protected function getAverage($arguments, $column, $period) {
        sleep(1);
        return null;
    }
}