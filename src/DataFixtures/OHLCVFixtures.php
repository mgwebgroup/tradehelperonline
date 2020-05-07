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
use App\Service\PriceHistory\OHLCV\Yahoo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use App\Entity\Instrument;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use App\Service\Exchange\Equities\NASDAQ;
use App\Service\Exchange\Equities\NYSE;


class OHLCVFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['OHLCV'];
    }

    public function load(ObjectManager $manager)
    {
        $output = new ConsoleOutput();

        $instrumentRepository = $manager->getRepository(\App\Entity\Instrument::class);
        $instrument = $instrumentRepository->findOneBySymbol('FB');
        $provider = null;

        $date = new \DateTime();
        $interval = [
            'daily' => new \DateInterval('P1D'),
            'weekly' => new \DateInterval('P1W'),
            'monthly' => new \DateInterval('P1M'),
            'yearly' => new \DateInterval('P1Y'),
        ];

        // daily 10 days back
        $open = 100;
        $sequence[1] = [
          [10, .1, 0.05, 1000],
          [10, .9, 0.05, 1000],
          [10, -.9, 0.05, 1000],
          [10, -.1, 0.05, 1000],
            [5, .5, 0.01, 1000],
        ];
        $gradient = 1;
        $data = [$provider, $date, $instrument, $interval['daily']];
        foreach ($sequence[1] as $name => $parameters) {
            array_push($data, $open);
            $data = array_merge($data, $parameters);

//            $func = 'generate' . \ucwords($name);
            $func = 'generate' . 'Candle';
            $ohlcv = call_user_func([$this, $func], ...$data);

            $manager->persist($ohlcv);
            $manager->flush();

            $open += $gradient;
            $date->sub($data[3]);
            $data = array_splice($data, 0, 4);
        }


        // weekly 10 weeks back


        // monthly

        // yearly


//        $output->writeln(sprintf('Imported symbol=%s', $symbol));

    }

    /**
     * @param string | null $provider
     * @param \DateTime $date
     * @param \App\Entity\Instrument $instrument
     * @param \DateInterval $interval
     * @param float $open
     * @param float $size
     * Solid hammer: $movement > 0
     * Hollow hammer: $movement < 0
     * @param float $movement as percent of $open
     * @param $volume
     * @return \App\Entity\OHLCVHistory $candle
     */
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

}
