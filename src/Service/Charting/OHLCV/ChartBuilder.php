<?php
/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Service\Charting\OHLCV;


use App\Entity\OHLCV\History;
use App\Service\Charting\ChartBuilderInterface;
use App\Service\Exchange\Catalog;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ChartBuilder implements ChartBuilderInterface
{
    private $chartFactory;

    private $styleLibrary;

    private $em;

    private $catalog;

    public function __construct(
      ChartFactory $chartFactory,
      StyleLibrary $styleLibrary,
      RegistryInterface $registry,
      Catalog $catalog
    )
    {
        $this->chartFactory = $chartFactory;
        $this->styleLibrary = $styleLibrary;
        $this->em = $registry->getManager();
        $this->catalog = $catalog;
    }

    /**
     * Builds a medium-sized chart with 100 candle bars
     * @param App\Entity\Instrument $instrument
     * @param \DateTime $date
     * @param \DateInterval $interval
     * @throws \App\Exception\ChartException
     * @throws \App\Exception\PriceHistoryException
     */
    public function buildMedium($instrument, $date, $interval, $FQFN)
    {
        $tradingCalendar  = $this->catalog->getExchangeFor($instrument)->getTradingCalendar();
        $tradingCalendar->getInnerIterator()->setStartDate($date)->setDirection(-1);
        $offset = 100;
        $limitIterator = new \LimitIterator($tradingCalendar, $offset, 1);
        $limitIterator->rewind();
        $fromDate = $limitIterator->current();

        $history = $this->em->getRepository(History::class)->retrieveHistory($instrument, $interval, $fromDate,
                                                                             $date);
        $style = $this->styleLibrary->getStyle('medium');
        $style->symbol = $instrument->getSymbol();
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

        if ($FQFN) {
            $style->chart_path .= '/'.$FQFN;
        }

        $chart = $this->chartFactory::create($style, $history);
        $chart->save_chart();
    }
}