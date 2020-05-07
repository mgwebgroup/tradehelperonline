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

class WeeklyIteratorTest extends KernelTestCase
{
    /**
     * @var App\Service\Exchange\WeeklyIterator
     */
    private $SUT;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(\App\Service\Exchange\WeeklyIterator::class);
    }


    public function testIntro()
    {
        // set start Date to non-Monday
        $this->SUT->setStartDate(new \DateTime('2020-05-07')); // Thursday
        $expected = '2020-05-04'; // Monday
        $this->assertSame($expected, $this->SUT->getStartDate()->format('Y-m-d'));

        $this->SUT->setStartDate(new \DateTime('2020-05-08')); // Friday
        $this->assertSame($expected, $this->SUT->getStartDate()->format('Y-m-d'));

        $this->SUT->setStartDate(new \DateTime('2020-05-09')); // Saturday
        $this->assertSame($expected, $this->SUT->getStartDate()->format('Y-m-d'));

        $this->SUT->setStartDate(new \DateTime('2020-05-10')); // Sunday
        $this->assertSame($expected, $this->SUT->getStartDate()->format('Y-m-d'));

        $this->SUT->setStartDate(new \DateTime('2020-05-04')); // Monday
        $this->assertSame($expected, $this->SUT->getStartDate()->format('Y-m-d'));

        $this->SUT->setStartDate(new \DateTime('2020-05-05')); // Tuesday
        $this->assertSame($expected, $this->SUT->getStartDate()->format('Y-m-d'));

        $this->SUT->setStartDate(new \DateTime('2020-05-06')); // Wednesday
        $this->assertSame($expected, $this->SUT->getStartDate()->format('Y-m-d'));

        $expected = '2020-05-11';
        $this->SUT->setStartDate(new \DateTime('2020-05-11')); // Monday
        $this->assertSame($expected, $this->SUT->getStartDate()->format('Y-m-d'));

        // set start Date to a Monday of a known Holiday
        $this->SUT->setStartDate(new \DateTime('2018-01-01')); // Monday
        $expected = '2018-01-02'; // Tuesday
        $this->assertSame($expected, $this->SUT->getStartDate()->format('Y-m-d'));
    }

    /**
     * Test that iterator returns dates
     */
    public function testDates10()
    {
        $expected = [
          '19991227' => '1999-12-27', // Monday
          '20000103' => '2000-01-03',
          '20000110' => '2000-01-10'
        ];
        $counter = 0;
        foreach ($this->SUT as $key => $value) {
            if ($counter > 2) break;

            $this->assertSame($expected[$key], $value->format('Y-m-d'));

            $counter++;
        }

        $this->SUT->setDirection(-1);

        $expected = [
          '21001227' => '2100-12-27',
          '21001220' => '2100-12-20',
          '21001213' => '2100-12-13'
        ];
        $counter = 0;
        foreach ($this->SUT as $key => $value) {
            if ($counter > 2) break;

            $this->assertSame($expected[$key], $value->format('Y-m-d'));

            $counter++;
        }
    }

    /**
     * Set the iterator to Monday of a know holiday and iterate for three weeks
     */
    public function testDates20()
    {
        $this->SUT->setStartDate(new \DateTime('2018-01-01')); // Monday

        $expected = [
          '20180102' => '2018-01-02', // Tuesday
          '20180108' => '2018-01-08',
          '20180116' => '2018-01-16' // Monday 2018-01-15 is MLK Day so we must see next day as beginning of the week
        ];
        $counter = 0;
        foreach ($this->SUT as $key => $value) {
            if ($counter > 2) break;

            $this->assertSame($expected[$key], $value->format('Y-m-d'));

            $counter++;
        }
    }

}