Installation
============

Applications that use Symfony Flex
----------------------------------

Open a command console enter your project directory and execute:

```bash
$ composer require mgwebgroup/market-survey
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the following command to download the latest stable version of this bundle:

```bash
$ composer require mgwebgroup/market-survey
```

This command requires you to have Composer installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md) of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    App\Studies\MGWebGroup\MarketSurvey\MarketSurveyBundle::class => ['all' => true],
];
```

### Step 3: Install and Compile Bundle Assets
Copy this bundle's assets as symlinks into _public/bundle/marketsurvey_ folder
```bash
$ bin/console assets:install public --symlink --relative
```
This bundle's file _webpack.config.js_ is already set to compile app's general assets (normally compiled with **npm run dev**). So you just need to compile this bundle's assets:
```bash
$ npx encore dev --config src/Studies/MGWebGroup/MarketSurvey/webpack.config.js
```

### Step 4: Import Study instruments, watch lists and formulas
The study utilizes trading instruments that are already imported into the system. If they are not imported, use the following command:
```bash
bin/console -v th:instruments:import data/source/x_universe.csv
bin/console -v th:instruments:import data/source/spdrs.csv
```
The commands will take the main index file _data/source/y_universe.csv_ (default) and together with _data/source/nasdaqlisted.csv_ and _data/source/otherlisted.csv_ will import all instruments. Two additional files _nasdaqlisted.csv_ and _otherlisted.csv_ are necessary to determine which stock exchange symbols belong to.

As mentioned above, price data on all instruments must be present for each imported symbol. Besides the daily prices the study uses weekly and monthly time frames. They should be present in the price history and saved in database (table _ohlcvhistory_). If not, run this command to create them from daily prices:
```bash
bin/console -v th:convert-ohlcv --weekly --monthly data/source/x_universe.csv
bin/console -v th:convert-ohlcv --weekly --monthly data/source/spdrs.csv
```

Import the study formulas:
```bash
bin/console -v th:expression:import --symbol=LIN --file data/studies/mgwebgroup/formulas/sitb.csv
bin/console -v th:expression:import --symbol=LIN --file data/studies/mgwebgroup/formulas/general.csv
```
You can specify any instrument for the _--symbol_ option. Price data on the instrument is used to test imported formulas. If you omit this option, the instrument will be selected at random. This may fail the import operation, because randomly chosen symbol may not have its price data.


The watch lists for the study in csv format are already present in data/studies/mgwebgroup/watchlists/ folder and must be imported with the following command:
```bash
bin/console -v th:watchlist:import data/studies/mgwebgroup/watchlists/y_universe.csv y_universe
bin/console -v th:watchlist:import data/studies/mgwebgroup/watchlists/spdr_sectors.csv spdr_sectors
```

Finally, to create studies, you must create first 20 studies with _market-breadth_ and _market-score_ attributes. The first studies are needed to figure out other parameters (study attributes) such as _score-table-rolling_, _score-table-mtd_, which summarize _market-score_ and _market-score-delta_ for the past 20 trading days.
Example below shows hypothetical start date of 2020-04-17
```bash
bin/console mgweb:studymanager --date=2020-04-17 y_universe spdr_sectors
... 19 more dates
bin/console mgweb:studymanager --date=2020-05-15 --full y_universe spdr_sectors
```

Testing
=======

Study tests rely on already imported instruments and their price data up to and including May 15th, 2020. Testing also must be done utilizing a separate database (i.e. _TRADEHLEPERONLINE_TEST_). Therefore make sure when running tests your database connection points to a test database. All instruments contained in test watch list with their price data must already be imported. Price data must be converted from daily into weekly, and monthly time frames. Running tests with data fixtures imported from the basic version of this package will not work. You must import instruments contained in _src/Studies/MGWebGroup/MarketSurvey/DataFixtures/watchlist_test.csv_, and you must have at a bare minimum daily prices, which you can then convert to weekly and monthly time frames. Price files may come with the basic version of the package, but it is not guaranteed.

1. Import all study formulas
Use commands from the Installation section for importing study formulas.

2. Import test watch list and studies
```bash
bin/console doctrine:fixtures:load --append --group=mgweb_watchlist
bin/console doctrine:fixtures:load --append --group=mgweb_sectors
bin/console doctrine:fixtures:load --append --group=mgweb_studies
```
Option _--append_ makes sure existing instruments and price data will not be purged.

3. Run study tests
```bash
bin/phpunit src/Studies/MGWebGroup/MarketSurvey/Tests/StudyBuilderTest.php
```

If you run into errors for finding price entries while running tests, make sure you have converted the daily prices into weekly and yearly for the symbol LIN. Also, erase the application cache pools:
```bash
rm -rf var/cache/test/pools
```


Definitions
===========

1. Market Survey

2. Market Survey Score (Market Score)
Assigns weights to each formula. 

3. Market Breadth
Market Survey and Market Score combined in one array.