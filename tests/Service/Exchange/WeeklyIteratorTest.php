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
use App\Service\Exchange\WeeklyIterator;
use App\Service\Exchange\Equities\TradingCalendar;

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
        $this->SUT = self::$container->get(WeeklyIterator::class);
    }

    public function testGetInnerIterator()
    {
        $innerIterator = $this->SUT->getInnerIterator();
        $this->assertInstanceOf(TradingCalendar::class, $innerIterator);
    }

    /**
     * StartDate is Monday, Jan-1st, 2018 is a holiday
     * Direction of DailyIterator is back
     * Expected: immediate Tuesday, Jan-2nd
     * @throws \Exception
     */
    public function testRewind10()
    {
        $startDate = new \DateTime('2018-01-01');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $this->SUT->rewind();
        $date = $this->SUT->current();
        $this->assertSame('2018-01-02', $date->format('Y-m-d'));
    }

    /**
     * StartDate is Saturday, Dec-30th, 2017
     * Direction of DailyIterator is back
     * Expected: previous Tuesday, Dec 26th, because Monday is Christmas
     * @throws \Exception
     */
    public function testRewind20()
    {
        $startDate = new \DateTime('2017-12-30');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $this->SUT->rewind();
        $date = $this->SUT->current();
        $this->assertSame('2017-12-26', $date->format('Y-m-d'));
    }

    /**
     * StartDate is Saturday, Dec-30th, 2017
     * Direction of DailyIterator is forward
     * Expected: previous Tuesday, Dec 26th, because Monday is Christmas
     * @throws \Exception
     */
    public function testRewind30()
    {
        $startDate = new \DateTime('2017-12-30');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(1);
        $this->SUT->rewind();
        $date = $this->SUT->current();
        $this->assertSame('2017-12-26', $date->format('Y-m-d'));
    }

    /**
     * StartDate is Monday, Jan-1st, 2018 is a holiday
     * Direction of DailyIterator is forward
     * Expected: immediate Tuesday, Jan-2nd
     * @throws \Exception
     */
    public function testRewind40()
    {
        $startDate = new \DateTime('2018-01-01');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $this->SUT->rewind();
        $date = $this->SUT->current();
        $this->assertSame('2018-01-02', $date->format('Y-m-d'));
    }

    /**
     *  Start Date is a DateTime object
     *  Expected: $date property in DailyIterator is a cloned object
     */
    public function testStartDate10()
    {
        $startDate = new \DateTime('2000-01-01'); // Saturday
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate);
        $date = $this->SUT->current();
        $this->assertNotSame($startDate, $date);
    }

    /**
     * StartDate is Saturday, Dec-30th, 2017
     * Iterate 3 weeks backward
     */
    public function testStepWeeks10()
    {
        $expected = [
          '2017-12-26', '2017-12-18', '2017-12-11'
        ];

        $startDate = new \DateTime('2017-12-30');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $counter = 0;
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $this->assertSame($expected[$counter], $value->format('Y-m-d'));
            $counter++;
        }
    }

    /**
     * StartDate is Saturday, Dec-30th, 2017
     * Iterate 3 weeks forward
     */
    public function testStepWeeks20()
    {
        $expected = [
          '2017-12-26', '2018-01-02', '2018-01-08'
        ];

        $startDate = new \DateTime('2017-12-30');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(1);
        $counter = 0;
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $this->assertSame($expected[$counter], $value->format('Y-m-d'));
            $counter++;
        }
    }

    /**
     * StartDate is Monday, Jan-1st, 2018
     * Iterate 3 weeks backward
     */
    public function testStepWeeks30()
    {
        $expected = [
          '2018-01-02', '2017-12-26', '2017-12-18'
        ];

        $startDate = new \DateTime('2018-01-01');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $counter = 0;
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $this->assertSame($expected[$counter], $value->format('Y-m-d'));
            $counter++;
        }
    }

    /**
     * StartDate is Monday, Jan-1st, 2018
     * Iterate 3 weeks forward
     */
    public function testStepWeeks40()
    {
        $expected = [
          '2018-01-02', '2018-01-08', '2018-01-16' // (Monday 01/15/2020 is MLK)
        ];

        $startDate = new \DateTime('2018-01-01');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(1);
        $counter = 0;
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $this->assertSame($expected[$counter], $value->format('Y-m-d'));
            $counter++;
        }
    }

    /**
     * StartDate is in middle of week, Jan-24th, 2018
     * Iterate 3 weeks forward
     */
    public function testStepWeeks50()
    {
        $expected = [
          '2018-01-22', '2018-01-29', '2018-02-05'
        ];

        $startDate = new \DateTime('2018-01-24');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(1);
        $counter = 0;
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $this->assertSame($expected[$counter], $value->format('Y-m-d'));
            $counter++;
        }
    }

    /**
     * StartDate is in middle of week, Jan-24th, 2018. Monday holiday is in the middle of 3 week backward iteration.
     * Iterate 3 weeks backward
     */
    public function testStepWeeks60()
    {
        $expected = [
          '2018-01-22', '2018-01-16', '2018-01-08'
        ];

        $startDate = new \DateTime('2018-01-24');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $counter = 0;
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $this->assertSame($expected[$counter], $value->format('Y-m-d'));
            $counter++;
        }
    }

    /**
     * StartDate is Monday, Jan-22nd, 2018 - work day
     * Iterate 3 weeks forward
     */
    public function testStepWeeks70()
    {
        $expected = [
          '2018-01-22', '2018-01-29', '2018-02-05'
        ];

        $startDate = new \DateTime('2018-01-22');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(1);
        $counter = 0;
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $this->assertSame($expected[$counter], $value->format('Y-m-d'));
            $counter++;
        }
    }

    /**
     * Check Boundaries
     * StarDate is first Monday after lower date boundary.
     * Iterate 3 weeks backward
     */
    public function testBoundaries10()
    {
        $startDate = new \DateTime('2000-01-03');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $counter = 0;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Date is below (older than) lower boundary of 2000-01-01T00:00:00+00:00');
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $counter++;
        }
    }

    /**
     * Check Boundaries
     * StarDate is last Friday before upper date boundary.
     * Iterate 3 weeks forward
     */
    public function testBoundaries20()
    {
        $startDate = new \DateTime('2100-12-31');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(1);
        $counter = 0;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Date is above (newer than) upper boundary of 2100-12-31T00:00:00+00:00');
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $counter++;
        }
    }
}