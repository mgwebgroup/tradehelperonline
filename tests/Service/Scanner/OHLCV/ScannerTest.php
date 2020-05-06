<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Service\Scanner\OHLCV;

use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ScannerTest extends KernelTestCase
{
    /**
     * @var \App\Service\PriceHistory\OHLCV\Yahoo
     */
    private $SUT;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(\App\Service\Scanner\OHLCV\Scanner::class);

//        $this->em = self::$container->get('doctrine')->getManager();
    }

    public function testIntro()
    {
//        fwrite(STDOUT, __METHOD__);
//        $this->SUT
//        $this->assertTrue(true);
//        $this->SUT->register('test', function ($str) {}, function($arguments, $str) {});
        $expr = $this->SUT->getExpressionInstance();
        $data = ['life' => 10, 'universe' => 10, 'everything' => 22];

//        $result = $expr->parse('1', [])->getNodes();
//        $result = $expr->parse('(2+2)/1', [])->getNodes();
//        $result = $expr->parse('1+2', [])->getNodes();
//        $result = $expr->compile('data["life"] + data["universe"] + data["everything"]', ['data']);
//        $result = $expr->compile('Close(10) - Close(20)');

        $result = $expr->evaluate('Close(10)', []);
        fwrite(STDOUT, $result);
    }

    /**
     * Tests operands that deal with past values, i.e. Close(10)
     */
    public function testScalarOperands10()
    {

    }

}
