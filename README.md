# Deployment pipeline:
# Configure:
cp .env.test .env

# Include bash scripts to formulate y_universe and lists for nyse, amex, and nasdaq
# ...

# Post-configure:
composer install

bin/console doctrine:database:create
bin/console doctrine:migrations:migrate --no-interaction 
bin/console doctrine:fixtures:load --group=Instruments --no-interaction
bin/console doctrine:fixtures:load --group=OHLCV --no-interaction --append

bin/phpunit tests/Service/Exchange



### Useful Queries

1. Show OHLCV History for symbol=TEST:
```mysql
select timestamp, i.symbol, open, high, low, close, volume, timeinterval, provider, o.id from ohlcvhistory o join instruments i on i.id=o.instrument_id where i.symbol = 'TEST' order by o.id;
```
