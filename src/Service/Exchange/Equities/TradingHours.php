<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Exchange\Equities;

trait TradingHours
{
    public function isTradingHours($datetime)
    {
        $secondsOffsetFromUTC = $datetime->format('Z');

        // check for holidays and weekends
        if (!$this->isTradingDay($datetime)) {
            return false;
        }
        // check for post trading hours
        elseif ($datetime->format('U') % 86400 + $secondsOffsetFromUTC > 16*3600 || $datetime->format('U') % 86400 + $secondsOffsetFromUTC < 9.5*3600 ) {
            return false;
        }

        // check for July 3rd: If July 4th occurs on a weekday and is not a substitute, prior trading day is open till 1300
        if ('07-03' == $datetime->format('m-d') && in_array($datetime->format('w'), [1,2,3,4])) {
            // var_dump($datetime->format('c'), $datetime->format('B'));
            return ($datetime->format('U') % 86400 + $secondsOffsetFromUTC > 13*3600)? false : true;
        }

        // check for post-Thanksgiving Friday: market is open till 1300 on this day
        $thanksGiving = new \DateTime('last Thursday of November this year');
        $thanksGiving->modify('next day');
        if ('11' == $datetime->format('m') && $thanksGiving->format('d') == $datetime->format('d')) {
            return ($datetime->format('U') % 86400 + $secondsOffsetFromUTC > 13*3600)? false : true;
        }

        // check for pre-Christmas day 24-Dec: If Christmas occurs on a weekday from Tuesday, prior trading day is open till 1300
        if ('12-24' == $datetime->format('m-d') && in_array($datetime->format('w'), [1,2,3,4])) {
            return ($datetime->format('U') % 86400 + $secondsOffsetFromUTC > 13*3600)? false : true;
        }

        // check for regular trading hours
        return ($datetime->format('U') % 86400 + $secondsOffsetFromUTC > 9.5*3600 && $datetime->format('U') % 86400 + $secondsOffsetFromUTC < 16*3600 )? true : false;
    }
}