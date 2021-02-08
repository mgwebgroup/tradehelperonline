<?php

/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Studies\MGWebGroup\MarketSurvey\Tests;

use App\Entity\Instrument;
use App\Entity\OHLCV\History;
use App\Service\Exchange\Equities\TradingCalendar;
use App\Service\Exchange\MonthlyIterator;
use App\Service\Exchange\WeeklyIterator;
use DateInterval;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Exception;

trait Formulas
{
    private function insideDayAndUp($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getDailyPrices($instrument, $date);

        if (
            ($p2->getHigh() >= $p1->getHigh()) and ($p2->getLow() <= $p1->getLow()) and ($p0->getClose() >=
            $p1->getHigh())
        ) {
            return true;
        }

        return false;
    }

    private function dBearishEng($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getDailyPrices($instrument, $date);

        if (
            ($p0->getClose() < $p1->getClose() && $p0->getClose() < $p1->getOpen()) &&
            ($p0->getOpen() > $p1->getClose() && $p0->getOpen() > $p1->getOpen())
        ) {
            return true;
        }

        return false;
    }

    private function dShtngStarAndDown($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getDailyPrices($instrument, $date);

        if (
            ((($p1->getHigh() - $p1->getLow()) > 3 * ($p1->getOpen() - $p1->getClose()) and
            (($p1->getHigh() - $p1->getClose()) / (0.001 + $p1->getHigh() - $p1->getLow()) > 0.6) and
            (($p1->getHigh() - $p1->getOpen()) / (0.001 + $p1->getHigh() - $p1->getLow()) > 0.6))) and
            $p0->getOpen() < $p1->getHigh() and $p0->getClose() < $p1->getLow()
        ) {
            return true;
        }

        return false;
    }

    private function insideWkAndUp($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getWeeklyPrices($instrument, $date);

        if (
            ($p2->getHigh() >= $p1->getHigh()) and ($p2->getLow() <= $p1->getLow()) and
            ($p0->getClose() >= $p1->getHigh())
        ) {
            return true;
        }

        return false;
    }

    private function wkBullishEng($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getWeeklyPrices($instrument, $date);

        if (
            ($p0->getOpen() < $p1->getOpen() and $p0->getOpen() < $p1->getClose()) and
            ($p0->getClose() > $p1->getOpen() and $p0->getClose() > $p1->getClose())
        ) {
            return true;
        }

        return false;
    }

    private function moShtngStar($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getMonthlyPrices($instrument, $date);

        if (
            ($p0->getHigh() - $p0->getLow()) > 3 * ($p0->getOpen() - $p0->getClose()) and
            (($p0->getHigh() - $p0->getClose()) / (0.001 + $p0->getHigh() - $p0->getLow()) > 0.6) and
            (($p0->getHigh() - $p0->getOpen()) / (0.001 + $p0->getHigh() - $p0->getLow()) > 0.6)
        ) {
            return true;
        }

        return false;
    }

    private function moBullishEng($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getMonthlyPrices($instrument, $date);

        if (
            ($p0->getOpen() < $p1->getOpen() and $p0->getOpen() < $p1->getClose()) and
            ($p0->getClose() > $p1->getOpen() and $p0->getClose() > $p1->getClose())
        ) {
            return true;
        }

        return false;
    }

    private function dayBO($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getDailyPrices($instrument, $date);

        if ($p0->getClose() - $p1->getHigh() > 0) {
            return true;
        }

        return false;
    }

    private function dayBD($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getDailyPrices($instrument, $date);

        if ($p0->getClose() - $p1->getLow() < 0) {
            return true;
        }

        return false;
    }

    private function posOnD($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getDailyPrices($instrument, $date);

        if ($p0->getClose() - $p0->getOpen() > 0) {
            return true;
        }

        return false;
    }

    private function negOnD($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getDailyPrices($instrument, $date);

        if ($p0->getClose() - $p0->getOpen() < 0) {
            return true;
        }

        return false;
    }

    private function weekBO($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getWeeklyPrices($instrument, $date);

        if ($p0->getClose() - $p1->getHigh() > 0) {
            return true;
        }

        return false;
    }

    private function weekBD($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getWeeklyPrices($instrument, $date);

        if ($p0->getClose() - $p1->getLow() < 0) {
            return true;
        }

        return false;
    }

    private function negOnWk($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getWeeklyPrices($instrument, $date);

        if ($p0->getClose() - $p0->getOpen() < 0) {
            return true;
        }

        return false;
    }

    private function posOnWk($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getWeeklyPrices($instrument, $date);

        if ($p0->getClose() - $p0->getOpen() > 0) {
            return true;
        }

        return false;
    }

    private function monthBO($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getMonthlyPrices($instrument, $date);

        if ($p0->getClose() - $p1->getHigh() > 0) {
            return true;
        }

        return false;
    }

    private function monthBD($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getMonthlyPrices($instrument, $date);

        if ($p0->getClose() - $p1->getLow() < 0) {
            return true;
        }

        return false;
    }

    private function posOnMo($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getMonthlyPrices($instrument, $date);

        if ($p0->getClose() - $p0->getOpen() > 0) {
            return true;
        }

        return false;
    }

    private function negOnMo($instrument, $date): bool
    {
        list($p0, $p1, $p2) = $this->getMonthlyPrices($instrument, $date);

        if ($p0->getClose() - $p0->getOpen() < 0) {
            return true;
        }

        return false;
    }

    private function getDailyPrices($instrument, $date): array
    {
        $oneDayAhead = clone $date;
        $oneDayAhead->add(new DateInterval('P1D'));
        $tradeDayIterator = self::$container->get(TradingCalendar::class);
        $tradeDayIterator->getInnerIterator()->setStartDate($oneDayAhead)->setDirection(-1);
        $tradeDayIterator->getInnerIterator()->rewind();
        $tradeDayIterator->next();
        return $this->getPrices($instrument, $tradeDayIterator);
    }

    private function getWeeklyPrices($instrument, $date): array
    {
        $weeklyIterator = self::$container->get(WeeklyIterator::class);
        $weeklyIterator->getInnerIterator()->getInnerIterator()->setStartDate($date)->setDirection(-1);
        $weeklyIterator->rewind();

        return $this->getPrices($instrument, $weeklyIterator);
    }

    private function getMonthlyPrices($instrument, $date): array
    {
        $monthlyIterator = self::$container->get(MonthlyIterator::class);
        $monthlyIterator->getInnerIterator()->getInnerIterator()->setStartDate($date)->setDirection(-1);
        $monthlyIterator->rewind();

        return $this->getPrices($instrument, $monthlyIterator);
    }

    private function getPrices($instrument, $iterator): array
    {
        $iteratorClass = get_class($iterator);
        switch ($iteratorClass) {
            case TradingCalendar::class:
                $interval = self::INTERVAL_DAILY;
                break;
            case WeeklyIterator::class:
                $interval = self::INTERVAL_WEEKLY;
                break;
            case MonthlyIterator::class:
                $interval = self::INTERVAL_MONTHLY;
                break;
            default:
                $interval = null;
        }

        $date = $iterator->current();
        $p0 = $this->getP($instrument, $date, $interval);

        $iterator->next();
        $p1 = $this->getP($instrument, $date, $interval);

        $iterator->next();
        $p2 = $this->getP($instrument, $date, $interval);

        return [$p0, $p1, $p2];
    }

    /**
     * @param Instrument $instrument
     * @param DateTime $date
     * @param String $interval
     * @return History $p
     * @throws Exception
     */
    private function getP(Instrument $instrument, DateTime $date, string $interval): History
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->em->getRepository(History::class)->createQueryBuilder('p');
        $queryBuilder->select('p')
          ->where('p.timeinterval = :interval')
          ->andWhere('p.timestamp = :date')
          ->andWhere('p.instrument = :instrument')
          ->setParameter('interval', $interval)
          ->setParameter('date', $date)
          ->setParameter('instrument', $instrument)
        ;
        $query = $queryBuilder->getQuery();
        $resultCacheLifetime = self::$container->getParameter('result_cache_lifetime');
//        $query->useResultCache(true, $resultCacheLifetime);
        $results = $query->execute();

        if (empty($results)) {
            throw new Exception(sprintf(
                'Could not get price data for %s, date %s, interval %s',
                $instrument->getSymbol(),
                $date->format('y-m-d H:i:s'),
                $interval
            ));
        }

        return $results[0];
    }
}
