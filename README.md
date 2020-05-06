
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


#### Price Scans

This section will describe scanners that use OHLCV Entities.

##### Formulas

Formulas work with scalar values and return scalar result. 

Operands in formulas can be dealing with today's price data, or with data involving past values. For example:
* get closing price for today: "Close" or "Close(0)";
* get opening price 2 days ago: "Open(2)".

Operands in formulas can also represent so called aggregate values. These values are calculated as one scalar which
involves several scalars. Example:
* 10 day moving average: "Average(10)";
* 20 day standard deviation: "Std_d(20)".

More complicated example would be to calculate a variance. Variance is determined by taking the difference between each 
given value and the average of all values given. Each of those differences is then squared, and the results are totaled.
The average of that total is then determined. Variance formula can be written as follows:
For 2 trading periods:
sum((Close(0) - Average(2))^2 + (Close(1) - Average(2))^2)/2

In this system operands for getting elements of the OHLCV format are represented by custom functions in the Symfony's 
Expression Builder. 





Simple formulas involve manipulation of fields readily available on entities, such as last Price, Open Price and so on.

Intermediate formulas handle entity fields X trading periods ago, and can be of form Close(3), Open(100), etc.

Advanced formulas handle mathematical functions such as Average, Sum, etc and can be more complex, thus involving
several functions.




Price scans perform simple expression searches, conditions of which  evaluate to boolean results. Examples:
* Find instruments where Last Closing Price > 100;
* Find instruments where Opening Price 3 days ago was below 200;
* Find instruments where 3 day Average of the Closing Price is above 25;

#### Price Formulas

Price Formulas return values, or arrays of values.




### Useful Queries

1. Show OHLCV History for symbol=TEST:
```mysql
select timestamp, i.symbol, open, high, low, close, volume, timeinterval, provider, o.id from ohlcvhistory o join instruments i on i.id=o.instrument_id where i.symbol = 'TEST' order by o.id;
```
