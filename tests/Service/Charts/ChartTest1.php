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
        $this->instrument = $this->em->getRepository(Instrument::class)->findOneBySymbol('LIN');
        $this->catalog = self::$container->get('App\Service\Exchange\Catalog');
        $this->styleLibrary = self::$container->get('App\Service\Charting\OHLCV\StyleLibrary');
    }

    public function testDefaultStyle10()
    {
        $interval = new \DateInterval('P1D');
        $OHLCVHistoryRepository = $this->em->getRepository(History::class);

        $tradingCalendar  = $this->catalog->getExchangeFor($this->instrument)->getTradingCalendar();
        $today = new \DateTime('2020-05-15');
        $tradingCalendar->getInnerIterator()->setStartDate($today)->setDirection(-1);
        $offset = 295;
        $limitIterator = new \LimitIterator($tradingCalendar, $offset, 1);
        $limitIterator->rewind();
        $fromDate = $limitIterator->current();

        $priceProvider = null;
        $history = $OHLCVHistoryRepository->retrieveHistory($this->instrument, $interval, $fromDate, $today, $priceProvider);

        $style = $this->styleLibrary->getStyle('default');

        $chart = ChartFactory::create($style, $history);
        $chart->save_chart();
    }

    public function testSmallStyle10()
    {
        $interval = new \DateInterval('P1D');
        $HistoryRepository = $this->em->getRepository(History::class);

        $tradingCalendar  = $this->catalog->getExchangeFor($this->instrument)->getTradingCalendar();
        $today = new \DateTime('2020-05-16');
        $tradingCalendar->getInnerIterator()->setStartDate($today)->setDirection(-1);
        $offset = 50;
        $limitIterator = new \LimitIterator($tradingCalendar, $offset, 1);
        $limitIterator->rewind();
        $fromDate = $limitIterator->current();

        $priceProvider = null;
        $history = $HistoryRepository->retrieveHistory($this->instrument, $interval, $fromDate, $today, $priceProvider);

        $style = $this->styleLibrary->getStyle('small');
        $style->symbol = $this->instrument->getSymbol();
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
        $historyRepository = $this->em->getRepository(History::class);

        $tradingCalendar  = $this->catalog->getExchangeFor($this->instrument)->getTradingCalendar();
        $today = new \DateTime('2020-05-16');
        $tradingCalendar->getInnerIterator()->setStartDate($today)->setDirection(-1);
        $offset = 100;
        $limitIterator = new \LimitIterator($tradingCalendar, $offset, 1);
        $limitIterator->rewind();
        $fromDate = $limitIterator->current();

        $priceProvider = null;
        $history = $historyRepository->retrieveHistory($this->instrument, $interval, $fromDate, $today, $priceProvider);

        $style = $this->styleLibrary->getStyle('medium');
        $style->symbol = $this->instrument->getSymbol();
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
        $chart->place_text([
          'sx' => 0,
          'sy' => round($chart->y_axis[0]['max'] * 0.99, 0),
          'text' => $this->instrument->getSymbol(),
          'color' => 'gray',
          'font_size' => '18'
        ]);
        $chart->save_chart();
    }

    public function testMediumStyle20()
    {
        $interval = new \DateInterval('P1D');
        $OHLCVHistoryRepository = $this->em->getRepository(History::class);

        $tradingCalendar  = $this->catalog->getExchangeFor($this->instrument)->getTradingCalendar();
        $today = new \DateTime('2020-05-16');
        $tradingCalendar->getInnerIterator()->setStartDate($today)->setDirection(-1);
        $offset = 100;
        $limitIterator = new \LimitIterator($tradingCalendar, $offset, 1);
        $limitIterator->rewind();
        $fromDate = $limitIterator->current();

        $priceProvider = null;
        $history = $OHLCVHistoryRepository->retrieveHistory($this->instrument, $interval, $fromDate, $today, $priceProvider);

        $style = $this->styleLibrary->getStyle('medium_b&b');
        $style->symbol = $this->instrument->getSymbol();
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

        if (isset($style->symbol)) {
            $chart->place_text(['sx' => 50, 'sy' => 230, 'text' => $style->symbol, 'font_size' => 30, 'color' => 'gray
            ']);
        }

        $chart->save_chart();
    }
}