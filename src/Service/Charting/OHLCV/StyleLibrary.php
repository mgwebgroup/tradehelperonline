<?php
/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Service\Charting\OHLCV;

use App\Service\Charting\StyleLibraryManager;

/**
 * This class is capable of taking style definitions from the service.yaml file through DI of service arguments.
 * Arguments to __construct must be processed and a style saved.
 * @package App\Service\Charting\OHLCV
 */
class StyleLibrary extends StyleLibraryManager
{
    public function __construct(string $projectRootDir)
    {
        $default = new Style();
        $default->chart_path = $projectRootDir;
        $this->addStyle($default);

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
        $style->chart_path = $projectRootDir;
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
        $style->font = $projectRootDir.'/'.$style->font;
        $this->addStyle($style);

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
        $style->chart_path = $projectRootDir;
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
        $style->font = $projectRootDir.'/'.$style->font;
        $this->addStyle($style);

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
        $style->chart_path = $projectRootDir;
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
        $style->color_up = 'blue';
        $style->color_down = 'black';
        $style->font = $projectRootDir.'/'.$style->font;
        $this->addStyle($style);
    }
}