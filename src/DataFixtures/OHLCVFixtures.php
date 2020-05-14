<?php
/**
 * This file is part of the Trade Helper package.
 *
 * (c) Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\OHLCVHistory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use App\Service\Exchange\MonthlyIterator;
use App\Service\Exchange\WeeklyIterator;
use App\Service\Exchange\Equities\TradingCalendar;
use App\Service\Exchange\DailyIterator;


class OHLCVFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['OHLCV'];
    }

    /**
     * Price data are imported as candlesticks of know characteristics. Candlesticks are described in $sequence arrays:
     * [size (absolute length from high to low), bodySize (percent), tail (absolute length from closing price to
     * high/low, volume)]
     * All items in a sequence go in reverse chronological order, i.e. most recent candlesticks first.
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $output = new ConsoleOutput();

        $tradingCalendar = new TradingCalendar(new DailyIterator());

        $instrumentRepository = $manager->getRepository(\App\Entity\Instrument::class);
        $instrument = $instrumentRepository->findOneBySymbol('FB');
        $provider = null;
        $interval = [
          'daily' => new \DateInterval('P1D'),
          'weekly' => new \DateInterval('P1W'),
          'monthly' => new \DateInterval('P1M'),
          'yearly' => new \DateInterval('P1Y'),
        ];

        $date = new \DateTime('2020-03-06'); // 6-March-2020 Friday
        $tradingCalendar->getInnerIterator()->setStartDate($date)->setDirection(-1);
        $tradingCalendar->getInnerIterator()->rewind();

        // daily 10 days back
        $open = 100;
        $sequence = [
          [10, .1, 0.05, 1001],
          [10, .9, 0.05, 1002],
          [10, -.9, 0.05, 1003],
          [10, -.1, 0.05, 1004],
          [5, .5, 0.01, 1005],
          [10, .1, 0.05, 1006],
          [10, .9, 0.05, 1007],
          [10, -.9, 0.05, 1008],
          [10, -.1, 0.05, 1009],
          [5, .5, 0.01, 1010],
        ];
        $gradient = 1;
        $data = [$provider, $date, $instrument, $interval['daily']];
        foreach ($sequence as $name => $parameters) {
            array_push($data, $open);
            $data = array_merge($data, $parameters);

//            $func = 'generate' . \ucwords($name);
//            $ohlcv = call_user_func([$this, $func], ...$data);
            $ohlcv = $this->generateCandle(...$data);

            $manager->persist($ohlcv);
            $manager->flush();

            $open += $gradient;
            $tradingCalendar->next();
            $data[1] = $tradingCalendar->current();
            $data = array_splice($data, 0, 4);
        }

        $output->writeln(sprintf('Imported daily prices for %s', $instrument->getSymbol()));


        // weekly 10 weeks back
        $tradingCalendar->getInnerIterator()->setStartDate($date)->setDirection(-1);
        $tradingCalendar->rewind();
        $weeklyIterator = new WeeklyIterator($tradingCalendar);
        $date = $weeklyIterator->seek(new \DateTime('2020-03-02')); // 2-March-2020 Monday

        $open = 200;
        $sequence = [
          [20, .1, 0.05, 2001],
          [20, .9, 0.05, 2002],
          [20, -.9, 0.05, 2003],
          [20, -.1, 0.05, 2004],
          [10, .5, 0.01, 2005],
          [20, .1, 0.05, 2006],
          [20, .9, 0.05, 2007],
          [20, -.9, 0.05, 2008],
          [20, -.1, 0.05, 2009],
          [10, .5, 0.01, 2010],
        ];
        $gradient = 1;
        $data = [$provider, $date, $instrument, $interval['weekly']];
        foreach ($sequence as $name => $parameters) {
            array_push($data, $open);
            $data = array_merge($data, $parameters);

//            $func = 'generate' . \ucwords($name);
//            $ohlcv = call_user_func([$this, $func], ...$data);
            $ohlcv = $this->generateCandle(...$data);

            $manager->persist($ohlcv);
            $manager->flush();

            $open += $gradient;
            $weeklyIterator->next();
            $data[1] = $weeklyIterator->current();
            $data = array_splice($data, 0, 4);
        }

        $output->writeln(sprintf('Imported weekly prices for %s', $instrument->getSymbol()));


        // monthly
        $tradingCalendar->getInnerIterator()->setStartDate($date)->setDirection(-1);
        $tradingCalendar->rewind();
        $monthlyIterator = new MonthlyIterator($tradingCalendar);

        $open = 300;
        $sequence = [
          [30, .1, 0.05, 3001],
          [30, .9, 0.05, 3002],
          [30, -.9, 0.05, 3003],
          [30, -.1, 0.05, 3004],
          [15, .5, 0.01, 3005],
          [30, .1, 0.05, 3006],
          [30, .9, 0.05, 3007],
          [30, -.9, 0.05, 3008],
          [30, -.1, 0.05, 3009],
          [15, .5, 0.01, 3010],
        ];
        $gradient = 1;
        $data = [$provider, $date, $instrument, $interval['monthly']];
        foreach ($sequence as $name => $parameters) {
            array_push($data, $open);
            $data = array_merge($data, $parameters);

//            $func = 'generate' . \ucwords($name);
//            $ohlcv = call_user_func([$this, $func], ...$data);
            $ohlcv = $this->generateCandle(...$data);

            $manager->persist($ohlcv);
            $manager->flush();

            $open += $gradient;
            $monthlyIterator->next();
            $data[1] = $monthlyIterator->current();
            $data = array_splice($data, 0, 4);
        }
        $output->writeln(sprintf('Imported monthly prices for %s', $instrument->getSymbol()));

        // yearly

    }


    /**
     * @param string | null $provider
     * @param \DateTime $date
     * @param \App\Entity\Instrument $instrument
     * @param \DateInterval $interval
     * @param float $open
     * @param float $size
     * Solid tall: $movement > 0
     * Hollow tall: $movement < 0
     * @param float $movement as percent of $open
     * @param float $upperTail
     * @param $volume
     * @return \App\Entity\OHLCVHistory $candle
     */
    public function generateCandle($provider, $date, $instrument, $interval, $open, $size, $bodySize, $tail, $volume)
    {
        $p = new OHLCVHistory();

        $p->setProvider($provider);
        $p->setTimestamp(clone $date);
        $p->setInstrument($instrument);
        $p->setTimeinterval($interval);
        $p->setOpen($open);
        $movement = $size * $bodySize;
        $close = $open + $movement;
        $p->setClose($close);
        if ($movement > 0) {
            $p->setHigh($close + $tail);
            $p->setLow($p->getHigh() - $size);
        } else {
            $p->setLow($close - $tail);
            $p->setHigh($p->getLow() + $size);
        }

        $p->setVolume($volume);

        return $p;
    }

//    /**
//     * @param string | null $provider
//     * @param \DateTime $date
//     * @param \App\Entity\Instrument $instrument
//     * @param \DateInterval $interval
//     * @param float $open
//     * @param float $size
//     * Solid hammer: $movement > 0
//     * Hollow hammer: $movement < 0
//     * @param float $movement as percent of $open
//     * @param $volume
//     * @return \App\Entity\OHLCVHistory $candle
//     */
//    public function generateHammer($provider, $date, $instrument, $interval, $open, $size, $movement, $volume)
//    {
//        $p = new OHLCVHistory();
//
//        $p->setProvider($provider);
//        $p->setTimestamp($date);
//        $p->setInstrument($instrument);
//        $p->setTimeinterval($interval);
//        $p->setOpen($open);
//        $p->setClose($open * (1 + $movement));
//        if ($movement > 0) {
//            $p->setHigh($p->getClose() + 0.1);
//        } else {
//            $p->setHigh($p->getOpen() + 0.1);
//        }
//        $p->setLow($p->getHigh() - $size);
//
//        $p->setVolume($volume);
//
//        return $p;
//    }

}
