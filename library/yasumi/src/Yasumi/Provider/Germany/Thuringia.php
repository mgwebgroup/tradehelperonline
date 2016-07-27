<?php
/**
 *  This file is part of the Yasumi package.
 *
 *  Copyright (c) 2015 - 2016 AzuyaLabs
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 *  @author Sacha Telgenhof <stelgenhof@gmail.com>
 */

namespace Yasumi\Provider\Germany;

use Yasumi\Provider\ChristianHolidays;
use Yasumi\Provider\Germany;
use Yasumi\Holiday;

/**
 * Provider for all holidays in Thuringia (Germany).
 *
 * The Free State of Thuringia is a federal state of Germany, located in the central part of the country. It has an area
 * of 16,171 square kilometres (6,244 sq mi) and 2.29 million inhabitants, making it the sixth smallest by area and the 
 * fifth smallest by population of Germany's sixteen states. Most of Thuringia is within the watershed of the Saale, a 
 * left tributary of the Elbe. Its capital is Erfurt.
 *
 * @link https://en.wikipedia.org/wiki/Thuringia
 */
class Thuringia extends Germany
{
    /**
     * Code to identify this Holiday Provider. Typically this is the ISO3166 code corresponding to the respective
     * country or subregion.
     */
    const ID = 'DE-TH';

    /**
     * Initialize holidays for Thuringia (Germany).
     */
    public function initialize()
    {
        parent::initialize();

        // Add custom Christian holidays
        $this->calculateReformationDay();
    }
}
