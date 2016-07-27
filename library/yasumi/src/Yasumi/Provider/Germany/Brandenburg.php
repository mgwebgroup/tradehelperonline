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
 * Provider for all holidays in Brandenburg (Germany).
 *
 * Brandenburg is one of the sixteen federated states of Germany. It lies in the northeast of the country covering an
 * area of 29,478 square kilometers and has 2.45 million inhabitants. The capital and largest city is Potsdam.
 * Brandenburg surrounds but does not include the national capital and city-state Berlin forming a metropolitan area.
 *
 * @link https://en.wikipedia.org/wiki/Brandenburg
 */
class Brandenburg extends Germany
{
    /**
     * Code to identify this Holiday Provider. Typically this is the ISO3166 code corresponding to the respective
     * country or subregion.
     */
    const ID = 'DE-BB';

    /**
     * Initialize holidays for Brandenburg (Germany).
     */
    public function initialize()
    {
        parent::initialize();

        // Add custom Christian holidays
        $this->calculateReformationDay();
    }
}
