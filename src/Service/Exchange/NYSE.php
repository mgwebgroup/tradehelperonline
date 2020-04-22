<?php

namespace App\Service\Exchange;


class NYSE extends Equities
{
    const SYMBOLS_LIST = 'data/source/otherlisted.csv';
    const TIMEZONE = 'America/New_York';

    public static function getExchangeName()
    {
        return str_replace(__NAMESPACE__ . '\\', '', __CLASS__);
    }

    public function getTradedInstruments()
    {
        return ($this->instrumentRepository->findBy(['exchange' => $this->getExchangeName()]));
    }

    public function isTraded($symbol)
    {
        return ($this->instrumentRepository->findOneBy(['symbol' => $symbol, 'exchange' => $this->getExchangeName()]))? true : false;
    }

    public function isOpen($dateTime)
    {
        $timeZone = new \DateTimeZone(self::TIMEZONE);
        $date = clone $dateTime;
        $date->setTimezone($timeZone);

        return parent::isOpen($date);
    }

    protected function matchHolidays($year)
    {
        // add Good Friday
        $this->holidaysProvider->addHoliday($this->holidaysProvider->goodFriday($year, self::TIMEZONE, 'en_US'));

        // remove columbusDay
        $this->holidaysProvider->removeHoliday('columbusDay');

        // remove veteransDay
        $this->holidaysProvider->removeHoliday('veteransDay');
    }
}
