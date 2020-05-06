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

class ScannerExpressionTest extends KernelTestCase
{
    /**
     * @var \App\Service\Scanner\OHLCV\ScannerExpression
     */
    private $SUT;

    private $em;

    private $instrument;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(\App\Service\Scanner\OHLCV\ScannerExpression::class);

        $this->em = self::$container->get('doctrine')->getManager();

        $this->instrument = $this->em->getRepository(\App\Entity\Instrument::class)->findOneBySymbol('FB');
    }

    public function testIntro()
    {
        $this->assertTrue(true);
    }

    /**
     * Function: Closing P
     * Period: Daily
     * Test getting past and present values
     */
    public function testClosingPrice10()
    {
        $expression = 'Close(1)';
        $result = $this->SUT->evaluate($expression, ['instrument' => $this->instrument]);
        fwrite(STDOUT, $result);
    }

    /**
     * Function: Open P
     * Period: Daily
     * Test getting past and present values
     */

    /**
     * Function: High P
     * Period: Daily
     * Test getting past and present values
     */


    /**
     * Function: Low P
     * Period: Daily
     * Test getting past and present values
     */

    /**
     * Function Volume
     * Period: Daily
     * Test getting past and present values
     */

    /**
     * Function Close P
     * Period: Weekly
     * Test getting past and present values
     */

    /**
     * Function Open P
     * Period Monthly
     * Test getting past and present values
     */

    /**
     * Function High P
     * Period Yearly
     * Test getting past and present values
     */

    /**
     * Function Low P
     * Period; Quarterly (not present)
     * Test getting exception
     */

    /**
     * Function Volume P
     * Period; Quarterly (not present)
     * Test getting exception
     */

    /**
     * Function Close P
     * Period: Daily
     * Test getting P which is not present in price history. Test getting exception
     */
    public function testException20()
    {

    }

    /**
     * Function Average P
     * Period: daily for 10 days
     */

    /**
     * Function Average P
     * Period: weekly
     * Range goes beyond existing price data
     * Test getting exception
     */
}
