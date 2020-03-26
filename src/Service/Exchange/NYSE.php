<?php

namespace App\Service\Exchange;


class NYSE extends Equities
{
    const NAME = 'NYSE';
    const SYMBOLS_LIST = 'data/source/nyse_companylist.csv';
    const TIMEZONE = 'America/New_York';
}
