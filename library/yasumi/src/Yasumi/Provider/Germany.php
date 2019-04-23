<?php

/**
 *  This file is part of the Yasumi package.
 *
 *  Copyright (c) 2015 - 2016 AzuyaLabs
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @author Sacha Telgenhof <stelgenhof@gmail.com>
 */

namespace Yasumi\Provider;

use DateTime;
use DateTimeZone;
use Yasumi\Holiday;

/**
 * Provider for all holidays in Germany.
 */
class Germany extends AbstractProvider
{
    use CommonHolidays, ChristianHolidays;

    /**
     * Code to identify this Holiday Provider. Typically this is the ISO3166 code corresponding to the respective
     * country or subregion.
     */
    const ID = 'DE';

    /**
     * Initialize holidays for Germany.
     */
    public function initialize()
    {
        $this->timezone = 'Europe/Berlin';

        // Add common holidays
        $this->addHoliday($this->newYearsDay($this->year, $this->timezone, $this->locale));

        // Add common Christian holidays (common in Germany)
        $this->addHoliday($this->ascensionDay($this->year, $this->timezone, $this->locale));
        $this->addHoliday($this->christmasDay($this->year, $this->timezone, $this->locale));
        $this->addHoliday($this->easter($this->year, $this->timezone, $this->locale));
        $this->addHoliday($this->easterMonday($this->year, $this->timezone, $this->locale));
        $this->addHoliday($this->goodFriday($this->year, $this->timezone, $this->locale));
        $this->addHoliday($this->internationalWorkersDay($this->year, $this->timezone, $this->locale));
        $this->addHoliday($this->newYearsDay($this->year, $this->timezone, $this->locale));
        $this->addHoliday($this->pentecost($this->year, $this->timezone, $this->locale));
        $this->addHoliday($this->pentecostMonday($this->year, $this->timezone, $this->locale));
        $this->addHoliday($this->secondChristmasDay($this->year, $this->timezone, $this->locale));

        // Calculate other holidays
        $this->calculateGermanUnityDay();

        // Note: all German states have agreed this to be a nationwide holiday in 2017 to celebrate the 500th anniversary.
        if ($this->year == 2017) {
            $this->calculateReformationDay();
        }
    }

    /**
     * German Unity Day.
     *
     * The Day of German Unity (German: Tag der Deutschen Einheit) is the national day of Germany, celebrated on
     * 3 October as a public holiday. It commemorates the anniversary of German reunification in 1990, when the
     * goal of a united Germany that originated in the middle of the 19th century, was fulfilled again. Therefore,
     * the name addresses neither the re-union nor the union, but the unity of Germany. The Day of German Unity on
     * 3 October has been the German national holiday since 1990, when the reunification was formally completed. It
     * is a legal holiday for the Federal Republic of Germany.
     *
     * @link https://en.wikipedia.org/wiki/German_Unity_Day
     */
    public function calculateGermanUnityDay()
    {
        if ($this->year >= 1990) {
            $this->addHoliday(new Holiday('germanUnityDay', ['de_DE' => 'Tag der Deutschen Einheit'],
                new DateTime($this->year . '-10-3', new \DateTimeZone($this->timezone)), $this->locale));
        }
    }

    /**
     * Calculates the day of the reformation.
     *
     * Reformation Day is a religious holiday celebrated on October 31, alongside All Hallows' Eve, in remembrance
     * of the Reformation. It is celebrated among various Protestants, especially by Lutheran and Reformed church
     * communities.
     * It is a civic holiday in the German states of Brandenburg, Mecklenburg-Vorpommern, Saxony, Saxony-Anhalt and
     * Thuringia. Slovenia celebrates it as well due to the profound contribution of the Reformation to that nation's
     * cultural development, although Slovenes are mainly Roman Catholics. With the increasing influence of
     * Protestantism in Latin America (particularly newer groups such as various Evangelical Protestants, Pentecostals
     * or Charismatics), it has been declared a national holiday in Chile in 2009.
     *
     * @link https://en.wikipedia.org/wiki/Reformation_Day
     * @link https://de.wikipedia.org/wiki/Reformationstag#Ursprung_und_Geschichte
     */
    public function calculateReformationDay()
    {
        $this->addHoliday(new Holiday('reformationDay', [
                'de_DE' => 'Reformationstag',
            ], new DateTime("$this->year-10-31", new DateTimeZone($this->timezone)), $this->locale));
    }
}
