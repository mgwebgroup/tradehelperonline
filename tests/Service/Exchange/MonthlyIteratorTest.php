<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Service\Exchange\DailyIterator;

use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MonthlyIteratorTest extends KernelTestCase
{
    /**
     * @var App\Service\Exchange\MonthlyIterator
     */
    private $SUT;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(\App\Service\Exchange\MonthlyIterator::class);
    }

    /**
     * Test that iterator returns dates
     */
//    public function testDates10()
//    {
//        $expected = [
//            '20000103' => '2000-01-03', // Monday
//            '20000110' => '2000-01-10',
//            '20000118' => '2000-01-18'
//        ];
//        $counter = 0;
//        foreach ($this->SUT as $key => $value) {
//            if ($counter > 2) break;
//            $this->assertSame($expected[$key], $value->format('Y-m-d'));
//
//            $counter++;
//        }
//
//        $this->SUT->getInnerIterator()->getInnerIterator()->setDirection(-1);
//
//        $expected = [
//          '21001227' => '2100-12-27',
//          '21001220' => '2100-12-20',
//          '21001213' => '2100-12-13'
//        ];
//        $counter = 0;
//        foreach ($this->SUT as $key => $value) {
//            if ($counter > 2) break;
//
//            $this->assertSame($expected[$key], $value->format('Y-m-d'));
//
//            $counter++;
//        }
//    }

    /**
     * Set the iterator to Monday of a known holiday and iterate for three weeks
     */
    public function testDates20()
    {
//        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate(new \DateTime('2018-01-01')); // Monday
//
//        $expected = [
//          '20180102' => '2018-01-02', // Tuesday
//          '20180108' => '2018-01-08',
//          '20180116' => '2018-01-16' // Monday 2018-01-15 is MLK Day so we must see next day as beginning of the week
//        ];
//        $counter = 0;
//        foreach ($this->SUT as $key => $value) {
//            if ($counter > 2) break;
//
//            $this->assertSame($expected[$key], $value->format('Y-m-d'));
//
//            $counter++;
//        }

        // iterate backwards where one of the weeks has a known holiday on Monday
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate(new \DateTime('2020-02-24')); // Monday
        $this->SUT->getInnerIterator()->getInnerIterator()->setDirection(-1);

        $expected = [
          '20200203' => '2020-02-03',
          '20200102' => '2020-01-02',
          '20191202' => '2019-12-02',
            '20191101' => '2019-11-01'
        ];
        $counter = 0;
        foreach ($this->SUT as $key => $value) {
            if ($counter > 3) break;

            $this->assertSame($expected[$key], $value->format('Y-m-d'));

            $counter++;
        }
    }

}