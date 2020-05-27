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

use App\Service\Charting\StyleInterface;

class Style implements StyleInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    public $x_axis;

    /**
     * @var array
     */
    public $y_axis;

    /**
     * @var array
     */
    private $line_prototypes;

    public $chart_path;

    public $percent_chart_area;

    public $symbol;

    public $font;

    public $width;

    public $height;

    public $upper_freeboard;

    public $lower_freeboard;

    public $categories;

    public $color_up;

    public $color_down;

    /**
     * @param string $name
     */
    public function __construct($name='default')
    {
        $this->name = $name;
        $this->x_axis = [
          'show' => TRUE,
          'min' => 0, 'max' => 300, 'upp' => 0,
          'y_intersect' => 300,
          'major_tick_size' => 8, 'minor_tick_size' => 4,
          'categories' => [],
          'major_interval' => 1, 'minor_interval_count' => 1,
          'axis_color' => 'black',
          'show_major_grid' => TRUE, 'show_minor_grid' => FALSE,
          'major_grid_style' => 'dash', 'minor_grid_style' => 'dash',
          'major_grid_color' => 'gray', 'minor_grid_color' => 'gray',
          'major_grid_scale' => 2, 'minor_grid_scale' => 1,
          'show_major_values' => TRUE, 'show_minor_values' => FALSE,
          'print_offset_major' => 30, 'print_offset_minor' => 35,
          'font_size' => 10,
          'precision' => 0,
          'font_angle' => 90,
        ];

        $this->y_axis = [
          'show' => TRUE,
          'min' => 0,
          'max' => 250,
          'upp' => 0,
          'x_intersect' => 0,
          'major_tick_size' => 8, 'minor_tick_size' => 4,
          'major_interval' => 5, 'minor_interval_count' => 2,
          'axis_color' => 'black',
          'show_major_grid' => TRUE, 'show_minor_grid' => TRUE,
          'major_grid_style' => 'dash', 'minor_grid_style' => 'dash',
          'major_grid_color' => 'gray', 'minor_grid_color' => 'gray',
          'major_grid_scale' => 2, 'minor_grid_scale' => 1,
          'show_major_values' => TRUE, 'show_minor_values' => TRUE,
          'print_offset_major' => 8, 'print_offset_minor' => 8,
          'font_size' => 10,
          'precision' => 0,
          'font_angle' => 0,
        ];

        $this->line_prototypes = [
          'default' => [[1 => 8]],
          'dash' => [[1 => 4], [0 => 4]],
          'dash_dot' => [[1 => 3], [0 => 2], [1 => 1], [0 => 2]]
        ];

        $this->chart_path = 'src/Studies/MyStudy/chart.png';
        $this->percent_chart_area = 90;
        $this->symbol = 'TEST';
        $this->font = 'assets/fonts/arial.ttf';
        $this->width = 4800;
        $this->height = 1800;

        $this->upper_freeboard = .03;
        $this->lower_freeboard = .03;

        $this->categories = [];
    }

}