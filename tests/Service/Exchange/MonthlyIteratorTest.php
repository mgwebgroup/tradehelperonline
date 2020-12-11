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

use App\Service\Exchange\Equities\TradingCalendar;
use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\Exchange\MonthlyIterator;

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
        $this->SUT = self::$container->get(MonthlyIterator::class);
    }

    public function testGetInnerIterator()
    {
        $innerIterator = $this->SUT->getInnerIterator();
        $this->assertInstanceOf(TradingCalendar::class, $innerIterator);
    }

    /**
     * StartDate is Tuesday, Jan 1st, 2019.
     * Direction of DailyIterator is back (must not affect $date)
     * Expected: next working day January, 2nd
     */
    public function testRewind10()
    {
        $startDate = new \DateTime('2019-01-01');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $this->SUT->rewind();
        $date = $this->SUT->current();
        $this->assertSame('2019-01-02', $date->format('Y-m-d'));
    }

    /**
     * StartDate is Tuesday, Jan 1st, 2019.
     * Direction of DailyIterator is forward (must not affect $date)
     * Expected: next working day January, 2nd
     */
    public function testRewind15()
    {
        $startDate = new \DateTime('2019-01-01');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(1);
        $this->SUT->rewind();
        $date = $this->SUT->current();
        $this->assertSame('2019-01-02', $date->format('Y-m-d'));
    }

    /**
     * StartDate is Saturday, Dec 1st, 2018.
     * Direction of DailyIterator is back (must not affect $date)
     * Expected: Monday, Dec-3rd
     */
    public function testRewind20()
    {
        $startDate = new \DateTime('2018-12-01');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $this->SUT->rewind();
        $date = $this->SUT->current();
        $this->assertSame('2018-12-03', $date->format('Y-m-d'));
    }

    /**
     * StartDate is Saturday, Dec 1st, 2018.
     * Direction of DailyIterator is forward (must not affect $date)
     * Expected: Monday, Dec-3rd
     */
    public function testRewind25()
    {
        $startDate = new \DateTime('2018-12-01');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(1);
        $this->SUT->rewind();
        $date = $this->SUT->current();
        $this->assertSame('2018-12-03', $date->format('Y-m-d'));
    }

    /**
     * StartDate is Wednesday, Jan 2nd, 2019
     * Direction of DailyIterator is back
     */
    public function testRewind30()
    {
        $startDate = new \DateTime('2019-01-02');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $this->SUT->rewind();
        $date = $this->SUT->current();
        $this->assertSame('2019-01-02', $date->format('Y-m-d'));
    }

    /**
     * StartDate is Wednesday, Dec 19th, 2018
     * Direction of DailyIterator is back
     */
    public function testRewind40()
    {
        $startDate = new \DateTime('2018-12-19');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $this->SUT->rewind();
        $date = $this->SUT->current();
        $this->assertSame('2018-12-03', $date->format('Y-m-d'));
    }

    /**
     * StartDate is Wednesday, Dec 19th, 2018
     * Direction of DailyIterator is forward
     */
    public function testRewind50()
    {
        $startDate = new \DateTime('2018-12-19');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(1);
        $this->SUT->rewind();
        $date = $this->SUT->current();
        $this->assertSame('2018-12-03', $date->format('Y-m-d'));
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
     * StartDate is Tuesday, Jan-1st, 2019
     * Iterate 3 months backward
     */
    public function testStepMonths10()
    {
        $expected = [
          '2019-01-02', '2018-12-03', '2018-11-01'
        ];

        $startDate = new \DateTime('2019-01-01');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $counter = 0;
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $this->assertSame($expected[$counter], $value->format('Y-m-d'));
            $counter++;
        }
    }

    /**
     * StartDate is Monday, Dec-31st, 2018
     * Iterate 3 months backward
     */
    public function testStepMonths20()
    {
        $expected = [
          '2018-12-03', '2018-11-01', '2018-10-01'
        ];

        $startDate = new \DateTime('2018-12-31');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(-1);
        $counter = 0;
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $this->assertSame($expected[$counter], $value->format('Y-m-d'));
            $counter++;
        }
    }

    /**
     * StartDate is Tuesday, Jan-1st, 2019
     * Iterate 3 months forward
     */
    public function testStepMonths30()
    {
        $expected = [
          '2019-01-02', '2019-02-01', '2019-03-01'
        ];

        $startDate = new \DateTime('2019-01-02');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(1);
        $counter = 0;
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $this->assertSame($expected[$counter], $value->format('Y-m-d'));
            $counter++;
        }
    }

    /**
     * StartDate is Thursday, Jan-31st, 2019
     * Iterate 3 months forward
     */
    public function testStepMonths40()
    {
        $expected = [
          '2019-01-02', '2019-02-01', '2019-03-01'
        ];

        $startDate = new \DateTime('2019-01-31');
        $this->SUT->getInnerIterator()->getInnerIterator()->setStartDate($startDate)->setDirection(1);
        $counter = 0;
        foreach ($this->SUT as $value) {
            if ($counter > 2) break;
            $this->assertSame($expected[$counter], $value->format('Y-m-d'));
            $counter++;
        }
    }

    /**
     * StartDate is Tuesday, Sep-2nd, 2019, Labor Day Holiday
     * Iterate 3 months forward
     */
    public function testStepMonths50()
    {
        $expected = [
          '2019-09-03', '2019-10-01', '2019-11-01'
        ];

        $startDate = new \DateTime('2019-09-02');
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
     * StarDate is first day of the lower date boundary.
     * Iterate 3 months backward
     */
    public function testBoundaries10()
    {
        $startDate = new \DateTime('2000-01-01');
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
     * StarDate is last day of the upper date boundary.
     * Iterate 3 months forward
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