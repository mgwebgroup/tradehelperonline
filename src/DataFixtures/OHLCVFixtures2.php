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

use App\Entity\OHLCV\History;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use League\Csv\Reader;
use Symfony\Component\Console\Output\ConsoleOutput;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;


class OHLCVFixtures2 extends Fixture implements FixtureGroupInterface
{
    const PATH = 'src/DataFixtures/LIN_d.csv';

    public static function getGroups(): array
    {
        return ['OHLCV2'];
    }

    /**
     * Imports real LIN ohlcv data from file src/DataFixtures/LIN_d.csv
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $output = new ConsoleOutput();

        $instrumentRepository = $manager->getRepository(\App\Entity\Instrument::class);
        $instrument = $instrumentRepository->findOneBySymbol('LIN');
        $provider = null;
        $interval = [
          'daily' => new \DateInterval('P1D'),
          'weekly' => new \DateInterval('P1W'),
          'monthly' => new \DateInterval('P1M'),
          'yearly' => new \DateInterval('P1Y'),
        ];

        $csv = Reader::createFromPath(self::PATH);
        $csv->setHeaderOffset(0);
        foreach ($csv->getRecords() as $key => $line) {
            $p = new History();

            $p->setProvider($provider);
            $p->setTimestamp(new \DateTime($line['Date']));
            $p->setInstrument($instrument);
            $p->setTimeinterval($interval['daily']);
            $p->setOpen($line['Open']);
            $p->setHigh($line['High']);
            $p->setLow($line['Low']);
            $p->setClose($line['Close']);
            $p->setVolume($line['Volume']);

            $manager->persist($p);
        }
        $manager->flush();

        $output->writeln(sprintf('Imported daily prices for %s', $instrument->getSymbol()));
    }
}
