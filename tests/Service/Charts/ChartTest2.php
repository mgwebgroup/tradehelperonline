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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\OHLCV\History;
use App\Entity\Instrument;
use App\Service\Charting\OHLCV\ChartFactory;

class ChartTest extends KernelTestCase
{
    private $em;

    private $instrument;

    private $catalog;

    private $styleLibrary;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::$container->get('doctrine')->getManager();
        $this->instrument = $this->em->getRepository(Instrument::class)->findOneBySymbol('FB');
        $this->catalog = self::$container->get('App\Service\Exchange\Catalog');
        $this->styleLibrary = self::$container->get('App\Service\Charting\OHLCV\StyleLibrary');
    }

    /**
     * Test intended for visually checking typical candle shapes that are encountered in scans. Will save chart only.
     */
    public function testCandleShapes()
    {
        $interval = new \DateInterval('P1D');
        $OHLCVHistoryRepository = $this->em->getRepository(History::class);

        $tradingCalendar  = $this->catalog->getExchangeFor($this->instrument)->getTradingCalendar();
        $toDate = new \DateTime('2020-03-09');
        $tradingCalendar->getInnerIterator()->setStartDate($toDate)->setDirection(-1);
        $offset = 20;
        $limitIterator = new \LimitIterator($tradingCalendar, $offset, 1);
        $limitIterator->rewind();
        $fromDate = $limitIterator->current();
        $history = $OHLCVHistoryRepository->retrieveHistory($this->instrument, $interval, $fromDate, $toDate);

        $style = $this->styleLibrary->getStyle('medium_b&b');
        $style->symbol = $this->instrument->getSymbol();
        $style->categories = array_map(function($p) { return $p->getTimestamp()->format('m/d'); }, $history);
        $style->x_axis['min'] = 0;
        $style->x_axis['max'] = 11;
        $style->x_axis['y_intersect'] = 11;
        $style->x_axis['major_interval'] = 1;
        $style->x_axis['minor_interval_count'] = 1;
        $style->y_axis['major_interval'] = 1;
        $style->y_axis['minor_interval_count'] = 1;

        $style->lower_freeboard = 0;
        $style->upper_freeboard = 0;

        $chart = ChartFactory::create($style, $history);
        $chart->save_chart(['path' => 'public/candle_shapes.png']);

        $this->assertFileExists('public/candle_shapes.png');
    }

}