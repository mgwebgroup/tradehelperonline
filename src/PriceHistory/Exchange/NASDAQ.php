<?php


namespace App\PriceHistory\Exchange;


interface NASDAQ extends \App\PriceHistory\Exchange\ExchangeInterface
{
    const NAME = 'NASDAQ';
    const SYMBOLS_LIST = 'data/source/nasdaq_companylist.csv';
}