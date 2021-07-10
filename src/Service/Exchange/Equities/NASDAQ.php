<?php
/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Service\Exchange\Equities;


class NASDAQ extends Exchange
{
    use TradingHours;

    const SYMBOLS_LIST = 'data/source/nasdaqlisted.csv';
    const TIMEZONE = 'America/New_York';

    public function isOpen($dateTime)
    {
        $timeZone = new \DateTimeZone(self::TIMEZONE);
        $date = clone $dateTime;
        $date->setTimezone($timeZone);

        return $this->isTradingHours($date);
    }

    public static function getExchangeName()
    {
        return str_replace(__NAMESPACE__ . '\\', '', __CLASS__);
    }
}
