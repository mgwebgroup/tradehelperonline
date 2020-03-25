<?php


namespace App\PriceHistory\Exchange;


interface NYSE extends \App\PriceHistory\Exchange\ExchangeInterface
{
    const NAME = 'NYSE';
    const SYMBOLS_LIST = 'data/source/nyse_companylist.csv';
}