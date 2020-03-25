<?php


namespace App\PriceHistory\Exchange;


interface AMEX extends \App\PriceHistory\Exchange\ExchangeInterface
{
    const NAME = 'AMEX';
    const SYMBOLS_LIST = 'data/source/amex_companylist.csv';
}