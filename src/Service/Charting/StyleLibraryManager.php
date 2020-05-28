<?php
namespace App\Service\Charting;

use App\Exception\ChartException;

/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class StyleLibraryManager
{
    /**
     * @var StyleInterface[]
     */
    private $styles;

    /**
     * @param string $name
     * @return StyleInterface $style
     * @throws ChartException
     */
    public function getStyle($name)
    {
        if (isset($this->styles[$name])) {
            return $this->styles[$name];
        } else {
            throw new ChartException(sprintf('Unknown style %s', $name));
        }
    }

    /**
     * @param StyleInterface $style
     * @return mixed
     * @throws ChartException
     */
    public function addStyle($style)
    {
        if ($style instanceof StyleInterface) {
            $this->styles[$style->getName()] = $style;
        } else {
            throw new ChartException('Style to add is not an instance of \App\Service\Charting\StyleInterface');
        }
    }
}