while (( "$#" > 0 )) ; do
  case $1 in
    bare)
      echo 'will perform bare install'
      cp .env.test .env
      # prep variables for the .env file
      echo "APP_ENV=$APP_ENV" >> .env
      echo "DATABASE_URL=\"$DATABASE_CONNECTOR\"" >> .env
      composer install
      ;;
    database)
      echo 'will create blank database and perform migrations'
      bin/console doctrine:database:create
      bin/console doctrine:migrations:migrate --no-interaction
      ;;
    data)
      echo 'will copy application data from aws'
      rclone --config=$RCLONE_CONFIG copy $DATA_REMOTE:$BUCKET_NAME/data/source /var/www/html/data/source
      bin/console instruments:import -v --clear-db=true
      ;;
    fixtures)
      # include check for non-prod environment here
      # ...
      echo 'will clear out existing database and add fixtures'
      bin/console doctrine:fixtures:load --group=Instruments --no-interaction
      ;;
    tests)
      echo 'will run unit tests'
      bin/phpunit tests/Service/Exchange
      bin/phpunit tests/Service/PriceHistory/OHLCV
      ;;
    *) echo "invalid directive $1"
      ;;
  esac
  shift
done

## Include bash scripts to formulate y_universe and lists for nyse, amex, and nasdaq
## ...
