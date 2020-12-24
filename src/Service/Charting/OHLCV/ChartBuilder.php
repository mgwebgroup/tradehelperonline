<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

    public function getStyleLibrary()
    {
        return $this->styleLibrary;
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

        $history = $this->em->getRepository(History::class)->retrieveHistory($instrument, $interval, $fromDate, $date);
        $priceForDate = $this->em->getRepository(History::class)->findOneBy(['instrument' => $instrument, 'timestamp'
        => $date, 'timeinterval' => $interval]);
        $history[] = $priceForDate;

        $style = $this->styleLibrary->getStyle('medium');
        $style->symbol = $instrument->getSymbol();
        $style->categories = array_map(function($p) { return $p->getTimestamp()->format('m/d'); }, $history);

        $tradingCalendar->getInnerIterator()->setStartDate($priceForDate->getTimestamp())->setDirection(1);
        $tradingCalendar->rewind();
        $tradingCalendar->next();
        end($style->categories);
        $i = key($style->categories);
        $i++;
        while ($i <= $style->x_axis['max']) {
            $style->categories[$i] = $tradingCalendar->current()->format('m/d');
            $tradingCalendar->next();
            $i++;
        }

        if ($FQFN) {
            $style->chart_path .= '/'.$FQFN;
        }

        $chart = $this->chartFactory::create($style, $history);
        $chart->save_chart();
    }
}