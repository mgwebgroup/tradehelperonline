cp .env.test .env

# prep variables for the .env file
echo "APP_ENV=$APP_ENV" >> .env
echo "DATABASE_URL=\"$DATABASE_CONNECTOR\"" >> .env

# Include bash scripts to formulate y_universe and lists for nyse, amex, and nasdaq
# ...

composer install

rclone --config=/home/$INSTANCE_USER/datastore.conf copy $DATA_REMOTE:$BUCKET_NAME/data/source /var/www/html/data/source

bin/console doctrine:database:create
bin/console doctrine:migrations:migrate --no-interaction 
bin/console doctrine:fixtures:load --group=Instruments --no-interaction
bin/console doctrine:fixtures:load --group=OHLCV --no-interaction --append

bin/phpunit tests/Service/Exchange
bin/phpunit tests/Service/PriceHistory/OHLCV