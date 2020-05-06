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

class DailyIteratorTest extends KernelTestCase
{
    /**
     * @var App\Service\Exchange\DailyIterator
     */
    private $SUT;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(\App\Service\Exchange\DailyIterator::class);
//        $this->em = self::$container->get('doctrine')->getManager();
    }


    public function testIntro()
    {
        $this->assertTrue(true);
    }

    /**
     * Test that iterator returns dates
     */
    public function testScalarOperands10()
    {
        $expected = [
          '20000101' => '2000-01-01',
          '20000102' => '2000-01-02',
          '20000103' => '2000-01-03'
        ];
        $counter = 0;
        foreach ($this->SUT as $key => $value) {
            if ($counter > 2) break;

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
            if ($counter > 2) break;

            $this->assertSame($expected[$key], $value->format('Y-m-d'));

            $counter++;
        }
    }
}