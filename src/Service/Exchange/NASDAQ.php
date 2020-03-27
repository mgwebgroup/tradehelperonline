<?php

namespace App\Service\Exchange;


class NASDAQ extends Equities
{
    const NAME = 'NASDAQ';
    const SYMBOLS_LIST = 'data/source/nasdaqlisted.csv';
    const TIMEZONE = 'America/New_York';

    protected function matchHolidays($year)
    {
        $this->holidaysProvider->addHoliday($this->holidaysProvider->goodFriday($year, self::TIMEZONE, 'en_US'));

        // remove columbusDay
        $this->holidaysProvider->removeHoliday('columbusDay');

        // remove veteransDay
        $this->holidaysProvider->removeHoliday('veteransDay');
    }
}
