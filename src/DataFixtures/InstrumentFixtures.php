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

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use App\Entity\Instrument;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use App\Service\Exchange\NASDAQ;
use App\Service\Exchange\NYSE;


class InstrumentFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * List of current company listings can be downloaded from NASDAQ website:
     * https://www.nasdaq.com/screening/company-list.aspx
     */
	const FILE = 'data/source/y_universe.csv';

    public static function getGroups(): array
    {
        return ['Instruments'];
    }

    public function load(ObjectManager $manager)
    {
        $output = new ConsoleOutput();

        $symbols = [
            'LIN,Linde plc Ordinary Share,NYSE',
            'FB,Facebook,NASDAQ'
        ];
    	foreach ($symbols as $line) {
            $fields = explode(',', $line);
        	$instrument = new Instrument();
            $symbol = strtoupper($fields[0]);
        	$instrument->setSymbol($symbol);

            if ($fields[2] == 'NYSE') {
                $instrument->setExchange(NYSE::getExchangeName());
            } elseif ($fields[2] == 'NASDAQ') {
                $instrument->setExchange(NASDAQ::getExchangeName());
            }

        	$instrument->setName($fields[1]);
        	$manager->persist($instrument);

            $output->writeln(sprintf('Imported symbol=%s', $symbol));
    	}

        $manager->flush();
    }
}
