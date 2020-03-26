<?php

namespace App\Service\Exchange;


class AMEX extends Equities
{
    const NAME = 'AMEX';
    const SYMBOLS_LIST = 'data/source/amex_companylist.csv';
    const TIMEZONE = 'America/New_York';
}
