<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Service\Charts;

use App\Entity\Instrument;
use App\Entity\OHLCV\History;
use \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\Charting\OHLCV\ChartBuilder;

class ChartBuilderTest extends KernelTestCase
{
    /**
     * @var App\Service\Charting\OHLCV\ChartBuilder
     */
    private $SUT;

    /**
     * @var App\Entity\Instrument
     */
    private $instrument;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->SUT = self::$container->get(ChartBuilder::class);
        $this->em = self::$container->get('doctrine')->getManager();
        $this->instrument = $this->em->getRepository(Instrument::class)->findOneBySymbol('LIN');
    }

    public function testBuildChartMedium10()
    {
        $date = new \DateTime('2020-05-15');
        $interval = History::getOHLCVInterval(History::INTERVAL_DAILY);
        $FQFN = sprintf('public/%s_%s', $this->instrument->getSymbol(), $date->format('Ymd'));
        $this->SUT->buildMedium($this->instrument, $date, $interval, $FQFN);
        $this->assertFileExists($FQFN);
        unlink($FQFN);
    }
}