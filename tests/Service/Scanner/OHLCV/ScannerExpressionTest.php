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

use App\Entity\OHLCVHistory;
use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use \App\Entity\Instrument;
use \App\Service\Scanner\OHLCV\ScannerExpression;

class ScannerExpressionTest extends KernelTestCase
{
    /**
     * @var \App\Service\Scanner\OHLCV\ScannerExpression
     */
    private $SUT;

    private $em;

    private $instrument;

    /**
     * @var \DateTime
     */
    private $latestDate;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(ScannerExpression::class);

        $this->em = self::$container->get('doctrine')->getManager();

        $this->instrument = $this->em->getRepository(Instrument::class)->findOneBySymbol('FB');

        $result = $this->em->getRepository(OHLCVHistory::class)->findBy(
          ['instrument' => $this->instrument],
          ['timestamp' => 'desc'],
          1
        );
        $OHLCVHistory = array_shift($result);
        $this->latestDate = clone $OHLCVHistory->getTimestamp();
    }

    public function testIntro()
    {
        $this->assertTrue(true);
    }

    /**
     * Period: Daily
     * Simple functions: Closing, Open, High, Low, Volume
     * Test getting past and present values
     */
    public function testSimpleFunctions10()
    {
        $_SERVER['TODAY'] = $this->latestDate->format('Y-m-d');
        $period = 'P1D';
        $expression = 'Close(1)';
        $data = [
            'instrument' => $this->instrument,
            'interval' => new \DateInterval($period),
          ];
        $result = $this->SUT->evaluate($expression, $data);
        $this->assertEquals(110, $result);

        $expression = 'Close(0)';
        $data = [
            'instrument' => $this->instrument,
        ];
        $result = $this->SUT->evaluate($expression, $data);
        $this->assertEquals(101, $result);

        $expression = 'Close(5)';
        $data = [
            'instrument' => $this->instrument,
            'date' => (clone $this->latestDate)->sub(new \DateInterval($period))
        ];
        $result = $this->SUT->evaluate($expression, $data);
        $this->assertEquals(115, $result);

        $_SERVER['TODAY'] = $this->latestDate->format('Y-m-d');
        $period = 'P1D';
        $expression = 'Open(1)';
        $data = [
          'instrument' => $this->instrument,
          'interval' => new \DateInterval($period),
        ];
        $result = $this->SUT->evaluate($expression, $data);
        $this->assertEquals(101, $result);

        $_SERVER['TODAY'] = $this->latestDate->format('Y-m-d');
        $period = 'P1D';
        $expression = 'High(1)';
        $data = [
          'instrument' => $this->instrument,
          'interval' => new \DateInterval($period),
        ];
        $result = $this->SUT->evaluate($expression, $data);
        $this->assertEquals(110.05, $result);

        $_SERVER['TODAY'] = $this->latestDate->format('Y-m-d');
        $period = 'P1D';
        $expression = 'Low(0)';
        $data = [
          'instrument' => $this->instrument,
          'interval' => new \DateInterval($period),
        ];
        $result = $this->SUT->evaluate($expression, $data);
        $this->assertEquals(91.05, $result);

        $_SERVER['TODAY'] = $this->latestDate->format('Y-m-d');
        $period = 'P1D';
        $expression = 'Volume(9)';
        $data = [
          'instrument' => $this->instrument,
          'interval' => new \DateInterval($period),
        ];
        $result = $this->SUT->evaluate($expression, $data);
        $this->assertEquals(1010, $result);
    }

    /**
     * Period: Weekly
     * Function Close P
     * Test getting past and present values
     */
    public function testSimpleFunctions20()
    {
        $_SERVER['TODAY'] = $this->latestDate->format('Y-m-d');
        $period = 'P7D';
        $expression = 'Close(1)';
        $data = [
          'instrument' => $this->instrument,
          'interval' => new \DateInterval($period),
        ];
        $result = $this->SUT->evaluate($expression, $data);
        $this->assertEquals(110, $result);
    }

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
//    public function testException20()
//    {
//
//    }

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
