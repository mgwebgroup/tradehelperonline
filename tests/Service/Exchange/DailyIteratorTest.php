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

use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\Exchange\DailyIterator;

class DailyIteratorTest extends KernelTestCase
{
    /**
     * @var DailyIterator
     */
    private $SUT;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(DailyIterator::class);
    }


    /**
     * Test that iterator returns dates
     */
    public function testDates10()
    {
        $expected = [
          '20000101' => '2000-01-01',
          '20000102' => '2000-01-02',
          '20000103' => '2000-01-03'
        ];
        $counter = 0;
        foreach ($this->SUT as $key => $value) {
            if ($counter > 2) {
                break;
            }

            $this->assertSame($expected[$key], $value->format('Y-m-d'));

            $counter++;
        }

        $this->SUT->setDirection(-1);

        $expected = [
          '21001231' => '2100-12-31',
          '21001230' => '2100-12-30',
          '21001229' => '2100-12-29'
        ];
        $counter = 0;
        foreach ($this->SUT as $key => $value) {
            if ($counter > 2) {
                break;
            }

            $this->assertSame($expected[$key], $value->format('Y-m-d'));

            $counter++;
        }
    }

    /**
     *  Start Date is a DateTime object
     *  Expected: $date property in DailyIterator is a cloned object
     */
    public function testStartDate10()
    {
        $startDate = new DateTime('2000-01-01'); // Saturday
        $this->SUT->setStartDate($startDate);
        $this->SUT->rewind();
        $date = $this->SUT->current();
        $this->assertNotSame($startDate, $date);
    }
}
