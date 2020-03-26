<?php

namespace App\Service\Exchange;


class NASDAQ extends Equities
{
    const NAME = 'NASDAQ';
    const SYMBOLS_LIST = 'data/source/nasdaq_companylist.csv';
    const TIMEZONE = 'America/New_York';
}
