<?php
/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Service\Charting\OHLCV;

use App\Entity\OHLCV\History;

/**
 * Wrapper class on top of the old Chart class
 * TODO: Rewrite the old chart class to be OOP and have Generators/Iterators for the axes
 * @package App\Service\Charting\OHLCV
 */
class ChartFactory
{
    /**
     * @param Style $style
     * @param array $data OHLCVHistory[]
     * @return App\Service\Charting\OHLCV\Chart $chart
     * @throws \App\Exception\ChartException
     */
    public static function create(Style $style, array $data)
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

        $open = array_map(function($p) { return $p->getOpen(); }, $data);
        $high = array_map(function($p) { return $p->getHigh(); }, $data);
        $low = array_map(function($p) { return $p->getLow(); }, $data);
        $close = array_map(function($p) { return $p->getClose(); }, $data);

        $x_axis = $style->x_axis;

        // adjust x-axis
        if (!empty($style->categories)) {
            $x_axis['categories'] = $style->categories;
        } else {
            $x_axis['categories'] = array_map(function($p) { return $p->getTimestamp()->format('m/d'); }, $data);
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