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
 * Provider for all holidays in Mecklenburg-Western Pomerania (Germany).
 *
 * Mecklenburg-Vorpommern (also known as Mecklenburg-Western Pomerania in English) is a federated state in northern 
 * Germany. The capital city is Schwerin. The state was formed through the merger of the historic regions of Mecklenburg
 * and Vorpommern after the Second World War, dissolved in 1952 and recreated at the time of the German reunification in
 * 1990.
 *
 * @link https://en.wikipedia.org/wiki/Mecklenburg-Vorpommern
 */
class MecklenburgWesternPomerania extends Germany
{
    /**
     * Code to identify this Holiday Provider. Typically this is the ISO3166 code corresponding to the respective
     * country or subregion.
     */
    const ID = 'DE-MV';

    /**
     * Initialize holidays for Mecklenburg-Western Pomerania (Germany).
     */
    public function initialize()
    {
        parent::initialize();

        // Add custom Christian holidays
        $this->calculateReformationDay();
    }
}
