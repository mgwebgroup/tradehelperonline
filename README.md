
### Components

#### General

All components are organized into Symphony services and are located in src/Service folder.


#### Price Download

Prices for instruments are downloaded from third party APIs and are handled via PriceProvider and PriceAdapter classes. 
PriceProvider handles a particular price provider, while PriceAdapter handles translation of API responses into this 
system's representation. For example, for the generic price provider Yahoo, interaction with its API may be implemented
by several third-party packages. Responses from the third party package are handled via adapter first, and then passed
on to the Price Provider.

Each exchange is represented by its own class, which handles implementation of responses about market hours and trading
days. An exchange usually trades in various types of commodities. Each type of commodity may have its own rules applied,
and in this way each exchange extends from the commodity type class, i.e. NASDAQ will be extending from the Equities
class.

Exchanges and Price Providers with Adapters have their own entities in the data structure. As of the time of this 
writing, the following is in place:
ExchangeInterface      - Instrument and InstrumentList entities
PriceProviderInterface - OHLCVHistory, OHLCVQuote entities

#### Price History

It is assumed that Price History may be stored in different formats. Most widely used format is OHLCV format, which 
graphically usually drawn as candlesticks. There is a dedicated data entity class that stores Price History. OHLCV price
history is stored without timezone in database. Time zone component is assumed to be in the time zone of the exchange.

As a general rule, timestamps dated with midnight are historical prices and represent the closing price for a trading 
day. Prices with times other than midnight represent quotes and are not the final closing prices.

#### Price Quotes

Price Quotes have their own dedicated Entity for the OHLCV domain. They store spot price for a given period: daily,
weekly, monthly, yearly etc.


##### Typical Use

To download prices during market hours:
```
bin/console price:sync -v --delay=random --chunk=50 --stillTsaveQ data/source/y_universe.csv
```
This command will handle the following scenarios as follows:
* Missing price history: download history from 2011-01-03 to T-1, then add current quote for T.
* Price history exists with last day earlier than T-1: download missing price history to T-1, then add current quote for
T.
* Price history exists with last day at T-1: add current quote for T. For a case when you have price at T-1 as quote
with timestamp earlier than market close for that day, use next command. This situation may occur if you were looking at
charts mid-day and needed current quotes before the close of T. The command below will download price for T-1 as 
history, thus making sure you don't have non-closing price saved as history.

To download prices after market close as historical. You need to be aware when prices for a T become supplied by 
 Yahoo API as historical. This time is after about 1800 EST (for NYC based exchanges such as NYSE and NASDAQ).
```
bin/console price:sync -v --delay=random --chunk=50 --prevT-QtoH data/source/y_universe.csv
``` 
This command will download historical prices if price at T-1 found to be a quote. Quotes in price history are
represented with time component other than midnight.


#### Price Scan





### Useful Queries

1. Show OHLCV History for symbol=TEST:
```mysql
select timestamp, i.symbol, open, high, low, close, volume, timeinterval, provider, o.id from ohlcvhistory o join instruments i on i.id=o.instrument_id where i.symbol = 'TEST' order by o.id;
```
