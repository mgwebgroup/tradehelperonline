
### Components

#### General

The system consists of the following components:
* Exchange
* Price History
* Expression Handler
* Scanner
* Watchlist
* Charting
* Utility Services

All components are organized into Symphony services and are located in src/Service folder.


#### Exchange Component
Marketplace exchanges usually trade in various types of commodities. Each type of commodity may have its own trading hours, rules for quotation, price format, etc. All exchange classes are named after exchange (NASDAQ, NYSE, etc.) and extend *Exchange* abstract class which implements the general *Exchange* Interface. Exchange classes are located in a dedicated directory for a type of traded commodity and handle responses about market hours, trading days and traded instruments. Example:
```text
Service/Exchange/Equities/NASDAQ.php
```


#### Price History Component
Prices for instruments are downloaded from third party APIs and are handled via PriceProvider and PriceAdapter classes. PriceProvider handles a particular price provider (Yahoo), while PriceAdapter handles translation of API responses into this system's representation. For example, for the generic price provider Yahoo, interaction with its API may be implemented by several third-party packages. Responses from the third party package are handled via adapter first, and then passed on to the Price Provider.



Exchanges and Price Providers with Adapters have their own entities in the data structure. As of the time of this 
writing, the following is in place:
ExchangeInterface      - Instrument and InstrumentList entities
PriceProviderInterface - OHLCVHistory, OHLCVQuote entities


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


### Deployment
All deployment operations are container-based, with Docker as the container application and one docker file. Deployment into a container depends on environment as each environment handles database service differently. Currently three environments are recognized:
* dev - database is a mix of production and test data as developer sees fit
* test - database is on the same instance as the application server and only contains test fixtures.
* prod - database is on a separate instance and contains only production data.

Environment variables that control the deployment process are:
* APP_ENV
* DB_USER=root
* DB_PASSWORD=mypassword
* DB_HOST=localhost
* DB_NAME
* DATA_REMOTE
* BUCKET_NAME

Entire application code and data consists of the following components:
* Code Base - (Github);
* Application Settings - feature toggles and parameters (AWS S3 bucket);
* Application Assets - particular to each app instance, graphical files, scripts and css (AWS S3 bucket);
* Application Data - data that needs to be stored in database (AWS S3 bucket);
* Test Fixtures - (Github)

*Dockerfile* is designed to work with script *deploy_app*. It has all necessary functionality for various stages of the deployment. Both files are committed to the repository. Parts of the *deploy_app* script are invoked for each deployment environment within the *Dockerfile*. In this way, you should only be able to configure deployment variables and build the application using one *Dockerfile*.

When building app image, all secret info is passed through the *--secret* flag to the docker builder. This flag contains reference to the rclone configuration, which has access credentials to AWS S3 storage. Example of using this flag:
```shell script
DOCKER_BUILDKIT=1 \
docker build \ 
--progress=plain \
--force-rm=true \
--build-arg=APP_ENV=test \
--build-arg=DATA_REMOTE=aws-mgwebgroup \
--build-arg=BUCKET_NAME=tradehelperonline \
-t tradehelperonline:test \
--secret id=datastore,src=$HOME/.config/rclone/rclone.conf \
.
```

File *rclone.conf* must contain bucket access configuration for AWS S3 bucket like so:
```text
[some-aws-remote]
type = s3
provider = AWS
env_auth = false
access_key_id = <access key for authorized AWS user>
secret_access_key = <Password for authorized AWS user>
region = <region>
location_constraint = <region>
acl = bucket-owner-full-control
```

Parent image in *Dockerfile* does not contain database, which is necessary for operation of entire application. In this way, whole application is deployed using two containers or *services*:
* MariaDB service
* Apache service

There are also several *docker-compose* files, which will launch application services configured for each environment:
* docker-compose.yml - Local developoment;
* docker-compose.test.yml - Testing environment
* docker-compose.prod.yml - Production environment

Separation into several docker-compose files is necessary for convenience of storage of all app data on dedicated volumes within the docker framework.  


#### Deployment to test environment
1. Create test app image using current files in the project root:
```shell script
DOCKER_BUILDKIT=1 \
docker build \
-f docker/Dockerfile \
--progress=plain \
--build-arg=APP_ENV=test \
--build-arg=DATA_REMOTE=aws-mgwebgroup \
--build-arg=BUCKET_NAME=tradehelperonline \
--build-arg=DB_NAME=TRADEHELPERONLINE_TEST \
--build-arg=DB_USER=user \
--build-arg=DB_PASSWORD=mypassword \
--build-arg=DB_HOST=172.24.1.3 \
-t tradehelperonline:test \
--secret id=datastore,src=$HOME/.config/rclone/rclone.conf \
.
```

2. Create container cluster:
```shell script
docker-compose -f docker/docker-compose.test.yml up -d
docker-compose -f docker/docker-compose.test.yml exec apache /var/www/html/deploy_app migrations
docker-compose -f docker/docker-compose.test.yml exec apache /var/www/html/deploy_app fixtures
docker-compose -f docker/docker-compose.test.yml exec apache /var/www/html/deploy_app tests
```

#### Deployment to prod environment
Production database must be set up separately.

1. Create prod app image using current files in the project root:
```shell script
DOCKER_BUILDKIT=1 \
docker build \
--progress=plain \
--build-arg=APP_ENV=prod \
--build-arg=DATA_REMOTE=aws-mgwebgroup \
--build-arg=BUCKET_NAME=tradehelperonline \
--build-arg=DB_NAME=TRADEHELPERONLINE_PROD \
--build-arg=DB_USER=user \
--build-arg=DB_PASSWORD=mypassword \
--build-arg=DB_HOST=172.24.1.3 \
-t calendar-p4t:prod \
--secret id=datastore,src=$HOME/.config/rclone/rclone.conf \
.
```

2. Import production database and create container cluster:
This script will map copy of your production database saved as *backups/TO_BE_PROD_DB.sql* to the *apache* service. Run it and use symfony's __doctrine:database:import__ command to import copy of the production database. After that you can bring up all containers normally.
```shell script
docker-compose -f docker-compose.prod.yml run --rm -v $(pwd)/backups:/var/www/html/akay -w /var/www/html apache dockerize -wait tcp4://mariadb:3306 bin/console doctrine:database:import backups/TO_BE_PROD_DB.sql 
docker-compose -f docker-compose.prod.yml up -d
```





### Useful Queries

1. Show OHLCV History for symbol=TEST:
```mysql
select timestamp, i.symbol, open, high, low, close, volume, timeinterval, provider, o.id from ohlcvhistory o join instruments i on i.id=o.instrument_id where i.symbol = 'TEST' order by o.id;
```
