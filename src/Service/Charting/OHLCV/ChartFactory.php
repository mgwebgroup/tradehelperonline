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

use App\Entity\OHLCVHistory;
use App\Service\Charting\OHLCV\Style;
use App\Service\Charting\OHLCV\Chart;


class ChartFactory
{
    /**
     * @param Style $style
     * @param OHLCVHistory[]
     * @return App\Service\Charting\OHLCV\Chart $chart
     */
    public static function create(Style $style, $history)
    {
        $canvas = [
          'path' => $style->chart_path,
          'percent_chart_area' => $style->percent_chart_area,
          'symbol' => $style->symbol,
          'ttf_font' => $style->font,
          'width' => $style->width,
          'height' => $style->height,
          'img_background' => 'gray',
          'chart_background' => 'white',
        ];

        $open = array_map(function($p) { return $p->getOpen(); }, $history);
        $high = array_map(function($p) { return $p->getHigh(); }, $history);
        $low = array_map(function($p) { return $p->getLow(); }, $history);
        $close = array_map(function($p) { return $p->getClose(); }, $history);

        $x_axis = $style->x_axis;

        // adjust x-axis
        if (!empty($style->categories)) {
            $x_axis['categories'] = $style->categories;
        } else {
            $x_axis['categories'] = array_map(function($p) { return $p->getTimestamp()->format('m/d'); }, $history);
        }

        $y_axis = $style->y_axis;

        // adjust y-axis
        $y_axis['min'] = min($low) * (1 - $style->lower_freeboard);
        $y_axis['max'] = max($high) * (1 + $style->upper_freeboard);
        $y_axis['x_intersect'] = $y_axis['min'];

        $chart = new Chart([$canvas, $x_axis, $y_axis]);

        $color_up = ($style->color_up)? : 'green';
        $color_down = ($style->color_down)? : 'red';

        $chart->add_candlestick_series(['open' => $open, 'high' => $high, 'low' => $low, 'close' => $close, 'color_up' => $color_up, 'color_down' => $color_down ]);

        return $chart;
    }
}