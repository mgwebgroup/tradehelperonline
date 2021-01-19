<?php

/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Service\Exchange;

use App\Service\Exchange\Equities\TradingCalendar;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\Exchange\DailyIterator;

class TradingCalendarTest extends KernelTestCase
{
    /**
     * @var TradingCalendar
     */
    private $SUT;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(TradingCalendar::class);
    }


    /**
     * Check that the trading calendar would not return holidays
     * Start from Thursday, Dec-31-2020
     * Count 20 days backwards
     */
    public function test10()
    {
        $dailyIterator = $this->SUT->getInnerIterator();
        $dailyIterator->setStartDate(new \DateTime('2020-12-31'))->setDirection(-1);


        $expected = [
            '2020-12-31',
            '2020-12-30',
            '2020-12-29',
            '2020-12-28',
            '2020-12-24',
            '2020-12-23',
            '2020-12-22',
            '2020-12-21',
            '2020-12-18',
            '2020-12-17',
            '2020-12-16',
            '2020-12-15',
            '2020-12-14',
            '2020-12-11',
            '2020-12-10',
            '2020-12-09',
            '2020-12-08',
            '2020-12-07',
            '2020-12-04',
            '2020-12-03',
            '2020-12-02'
        ];

        $counter = 1;
        $actual = [];
        foreach ($this->SUT as $key => $value) {
            if ($counter > 21) {
                break;
            }
//            fwrite(STDOUT, sprintf("%3d %s\n", $counter, $value->format('Y-m-d')));
//            fwrite(STDOUT, sprintf("%s\n", $value->format('Y-m-d')));
            $actual[] = $value->format('Y-m-d');

            $counter++;
        }
        $this->assertArraySubset($actual, $expected);
    }
}
