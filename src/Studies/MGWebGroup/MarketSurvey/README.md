Installation
============

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```bash
$ composer require mgwebgroup/market-survey
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require mgwebgroup/market-survey
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    App\Studies\MGWebGroup\MarketSurvey\MarketSurveyBundle::class => ['all' => true],
];
```

### Step 3: Install and Compile Bundle Assets
Copy this bundle's assets as symlinks into *public/bundle/marketsurvey* folder 
```bash
$ bin/console assets:install public --symlink --relative
```
This bundle's file *webpack.config.js* is already set to compile app's general assets (normally compiled with **npm run dev**). So you just need to compile this bundle's assets: 
```bash
$ npx encore dev --config src/Studies/MGWebGroup/MarketSurvey/webpack.config.js
```

### Step 4: Import Study symbols, watchlists and formulas
The study utilizes common symbols that are already imported into the main system. If they are not already imported, use the following command:
```bash
bin/console -v th:instruments:import
```
It will take the main index file (default: data/source/y_universe.csv) and together with data/source/nasdaqlisted.csv and data/source/otherlisted.csv will import all instruments. Additional files are necessary for determination which stock exchange they belong to.
Importation of instruments is important not only for the study function, but also for testing. All data fixtures rely on the common instrument base as well as daily, weekly, monthly, quarterly and yearly price data.

It is assumed that price data on all instruments is current, or at least present for each imported symbol. Besides the daily prices, the study uses weekly, monthly, quarterly and yearly time frames. They should be present in the price history and saved in database (table ohlcvhistory). If not, run this command to create them:
```bash
bin/console -v th:convert-ohlcv --weekly --monthly --quarterly data/source/y_universe.csv
```

Import the study formulas:
```bash
bin/console -v th:expression:import --file data/studies/mgwebgroup/formulas/sitb.csv
```

The watchlists for the study in csv format are already present in data/studies/mgwebgroup/watchlists/ folder and must be imported with the following command:
```bash
bin/console -v th:watchlist:import data/studies/mgwebgroup/watchlists/y_universe.csv y_universe
```
Watchlist named **y_universe** contains formulas necessary for market scoring (market survey).


Testing
=======

Study tests rely on already imported instruments and their price data. Testing also must be done utilizing a separate database (i.e. TRADEHLEPERONLINE_TEST). Therefore make sure when running tests your database connection points to a test database. All of the instruments with their latest price data must already be imported and converted from daily into weekly, monthly, quarterly and monthly time frames. Running tests with data fixtures imported from the basic version of this package will not work. You must import instruments from the general instrument list and you must have at a bare minimum daily prices for the year 2020 for all instruments. General instrument list titled 'y_universe' is supplied with the basic version of this package and is located in data/source/y_universe.csv file. You may either import entire database with instruments and prices (the fastest way), or import instruments and prices using csv files and built in commands. Price files may come with the basic version of the package but it is not guaranteed.

1. Import test watchlist
```bash
bin/console doctrine:fixtures:load --append --group=mgweb_watchlist
```
Option --append makes sure existing instruments and price data will not be purged.


Definitions
===========

1. Watchlist Survey
Run a count for each formula and criterion in a watchlist to see how many instruments match. Result of the survey is an array:
```php
$survey = [
    'Ins D & Up' => [$instrument1, $instrument2],
];
```

2. Watchlist Score (Market Score)
Assigns weights to each formula. 

3. Market Breadth
Watchlist Survey and Watchlist Score combined in one array.