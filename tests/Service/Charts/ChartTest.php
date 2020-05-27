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
use App\Entity\OHLCVHistory;
use App\Entity\Instrument;
use App\Service\Charting\OHLCV\ChartFactory;
use App\Service\Charting\OHLCV\Style;

class ChartTest extends KernelTestCase
{
    private $em;

    private $instrument;

    private $catalog;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::$container->get('doctrine')->getManager();
        $this->instrument = $this->em->getRepository(Instrument::class)->findOneBySymbol('LIN');
        $this->catalog = self::$container->get('App\Service\Exchange\Catalog');
    }

    public function testDefaultStyle10()
    {
        $interval = new \DateInterval('P1D');
        $OHLCVHistoryRepository = $this->em->getRepository(OHLCVHistory::class);

        $tradingCalendar  = $this->catalog->getExchangeFor($this->instrument)->getTradingCalendar();
        $today = new \DateTime('2020-05-15');
        $tradingCalendar->getInnerIterator()->setStartDate($today)->setDirection(-1);
        $offset = 295;
        $limitIterator = new \LimitIterator($tradingCalendar, $offset, 1);
        $limitIterator->rewind();
        $fromDate = $limitIterator->current();

        $priceProvider = null;
        $history = $OHLCVHistoryRepository->retrieveHistory($this->instrument, $interval, $fromDate, $today, $priceProvider);
        $style = new Style();

        $chart = ChartFactory::create($style, $history);
        $chart->save_chart();
    }

    public function testSmallStyle10()
    {
        $interval = new \DateInterval('P1D');
        $OHLCVHistoryRepository = $this->em->getRepository(OHLCVHistory::class);

        $tradingCalendar  = $this->catalog->getExchangeFor($this->instrument)->getTradingCalendar();
        $today = new \DateTime('2020-05-16');
        $tradingCalendar->getInnerIterator()->setStartDate($today)->setDirection(-1);
        $offset = 50;
        $limitIterator = new \LimitIterator($tradingCalendar, $offset, 1);
        $limitIterator->rewind();
        $fromDate = $limitIterator->current();

        $priceProvider = null;
        $history = $OHLCVHistoryRepository->retrieveHistory($this->instrument, $interval, $fromDate, $today, $priceProvider);


        $style = new Style('small');
        $style->width = 300;
        $style->height = 200;
        $style->y_axis['print_offset_major'] = 2;
        $style->y_axis['show_minor_values'] = false;
        $style->y_axis['minor_interval_count'] = 5;
        $style->y_axis['show_minor_grid'] = false;
        $style->y_axis['font_size'] = 8;
        $style->y_axis['major_tick_size'] = 2;
        $style->y_axis['minor_tick_size'] = 0;
        $style->y_axis['precision'] = 0;
        $style->chart_path = 'src/Studies/MyStudy/chart_small.png';
        $style->symbol = $this->instrument->getSymbol();
        $style->percent_chart_area = 70;
        $style->x_axis['min'] = -1;
        $style->x_axis['max'] = 55;
        $style->x_axis['y_intersect'] = 55;
        $style->x_axis['major_interval'] = 5;
        $style->x_axis['minor_interval_count'] = 5;
        $style->x_axis['font_size'] = 8;
        $style->x_axis['major_tick_size'] = 2;
        $style->x_axis['minor_tick_size'] = 0;
        $style->x_axis['print_offset_major'] = 14;
        $style->x_axis['show_major_grid'] = FALSE;
        $style->categories = array_map(function($p) { return $p->getTimestamp()->format('d'); }, $history);

        $keys = array_keys($history);
        $lastPriceHistoryKey = array_pop($keys);
        $tradingCalendar->getInnerIterator()->setStartDate($history[$lastPriceHistoryKey]->getTimeStamp())
          ->setDirection(1);
        $tradingCalendar->rewind();

        $keys = array_keys($style->categories);
        $key = array_pop($keys);

        while ($key <= $style->x_axis['max']) {
            $style->categories[$key] = $tradingCalendar->current()->format('d');
            $tradingCalendar->next();
            $key++;
        }

        $chart = ChartFactory::create($style, $history);
        $chart->save_chart();
    }

    public function testMediumStyle10()
    {
        $interval = new \DateInterval('P1D');
        $OHLCVHistoryRepository = $this->em->getRepository(OHLCVHistory::class);

        $tradingCalendar  = $this->catalog->getExchangeFor($this->instrument)->getTradingCalendar();
        $today = new \DateTime('2020-05-16');
        $tradingCalendar->getInnerIterator()->setStartDate($today)->setDirection(-1);
        $offset = 100;
        $limitIterator = new \LimitIterator($tradingCalendar, $offset, 1);
        $limitIterator->rewind();
        $fromDate = $limitIterator->current();

        $priceProvider = null;
        $history = $OHLCVHistoryRepository->retrieveHistory($this->instrument, $interval, $fromDate, $today, $priceProvider);


        $style = new Style('medium');
        $style->width = 800;
        $style->height = 600;
        $style->y_axis['print_offset_major'] = 2;
        $style->y_axis['show_minor_values'] = false;
        $style->y_axis['minor_interval_count'] = 5;
        $style->y_axis['show_minor_grid'] = false;
        $style->y_axis['font_size'] = 8;
        $style->y_axis['major_tick_size'] = 4;
        $style->y_axis['minor_tick_size'] = 0;
        $style->y_axis['precision'] = 0;
        $style->chart_path = 'src/Studies/MyStudy/chart_medium.png';
        $style->symbol = $this->instrument->getSymbol();
        $style->percent_chart_area = 70;
        $style->x_axis['min'] = -1;
        $style->x_axis['max'] = 105;
        $style->x_axis['y_intersect'] = 105;
        $style->x_axis['major_interval'] = 5;
        $style->x_axis['minor_interval_count'] = 5;
        $style->x_axis['font_size'] = 8;
        $style->x_axis['major_tick_size'] = 2;
        $style->x_axis['minor_tick_size'] = 0;
        $style->x_axis['print_offset_major'] = 32;
        $style->x_axis['show_major_grid'] = FALSE;
        $style->categories = array_map(function($p) { return $p->getTimestamp()->format('m/d'); }, $history);

        $keys = array_keys($history);
        $lastPriceHistoryKey = array_pop($keys);
        $tradingCalendar->getInnerIterator()->setStartDate($history[$lastPriceHistoryKey]->getTimeStamp())
          ->setDirection(1);
        $tradingCalendar->rewind();

        $keys = array_keys($style->categories);
        $key = array_pop($keys);

        while ($key <= $style->x_axis['max']) {
            $style->categories[$key] = $tradingCalendar->current()->format('m/d');
            $tradingCalendar->next();
            $key++;
        }

        $chart = ChartFactory::create($style, $history);
        $chart->save_chart();
    }

    public function testMediumStyle20()
    {
        $interval = new \DateInterval('P1D');
        $OHLCVHistoryRepository = $this->em->getRepository(OHLCVHistory::class);

        $tradingCalendar  = $this->catalog->getExchangeFor($this->instrument)->getTradingCalendar();
        $today = new \DateTime('2020-05-16');
        $tradingCalendar->getInnerIterator()->setStartDate($today)->setDirection(-1);
        $offset = 100;
        $limitIterator = new \LimitIterator($tradingCalendar, $offset, 1);
        $limitIterator->rewind();
        $fromDate = $limitIterator->current();

        $priceProvider = null;
        $history = $OHLCVHistoryRepository->retrieveHistory($this->instrument, $interval, $fromDate, $today, $priceProvider);

        $style = new Style('medium_b&b');
        $style->width = 800;
        $style->height = 600;
        $style->y_axis['print_offset_major'] = 2;
        $style->y_axis['show_minor_values'] = false;
        $style->y_axis['minor_interval_count'] = 5;
        $style->y_axis['show_minor_grid'] = false;
        $style->y_axis['font_size'] = 8;
        $style->y_axis['major_tick_size'] = 4;
        $style->y_axis['minor_tick_size'] = 0;
        $style->y_axis['precision'] = 0;
        $style->chart_path = 'src/Studies/MyStudy/chart_medium_b&b.png';
        $style->symbol = $this->instrument->getSymbol();
        $style->percent_chart_area = 70;
        $style->x_axis['min'] = -1;
        $style->x_axis['max'] = 105;
        $style->x_axis['y_intersect'] = 105;
        $style->x_axis['major_interval'] = 5;
        $style->x_axis['minor_interval_count'] = 5;
        $style->x_axis['font_size'] = 8;
        $style->x_axis['major_tick_size'] = 2;
        $style->x_axis['minor_tick_size'] = 0;
        $style->x_axis['print_offset_major'] = 32;
        $style->x_axis['show_major_grid'] = FALSE;
        $style->categories = array_map(function($p) { return $p->getTimestamp()->format('m/d'); }, $history);
        $style->color_up = 'blue';
        $style->color_down = 'black';

        $keys = array_keys($history);
        $lastPriceHistoryKey = array_pop($keys);
        $tradingCalendar->getInnerIterator()->setStartDate($history[$lastPriceHistoryKey]->getTimeStamp())
          ->setDirection(1);
        $tradingCalendar->rewind();

        $keys = array_keys($style->categories);
        $key = array_pop($keys);

        while ($key <= $style->x_axis['max']) {
            $style->categories[$key] = $tradingCalendar->current()->format('m/d');
            $tradingCalendar->next();
            $key++;
        }

        $chart = ChartFactory::create($style, $history);

        if (isset($style->symbol)) {
            $chart->place_text(['sx' => 50, 'sy' => 230, 'text' => $style->symbol, 'font_size' => 30, 'color' => 'gray']);
        }

        $chart->save_chart();
    }
}