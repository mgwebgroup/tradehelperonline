Installation
============

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require mgwebgroup/market-survey
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
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
```console
$ bin/console assets:install public --symlink --relative
```
This bundle's file *webpack.config.js* is already set to compile app's general assets (normally compiled with **npm run dev**). So you just need to compile this bundle's assets: 
```console
$ npx encore dev --config src/Studies/MGWebGroup/MarketSurvey/webpack.config.js
```

### Step 4: Import Study symbols, watchlists and formulas
The study utilizes common symbols that are already imported into the main system. If they are not already imported, use the following command:
```bash
bin/console -v th:instruments:import
```
It will take the main index file (default: data/source/y_universe.csv) and together with data/source/nasdaqlisted.csv and data/source/otherlisted.csv, which area necessary for determination which stock exchange they belong to, will import all instruments.

It is assumed that price data on all instruments is current, or at least present for each imported symbol. Besides the daily prices, the study uses weekly, monthly, quarterly and yearly time frames. They should be present in the price history and saved in database (table ohlcvhistory). If not, run this command to create them:
```bash
bin/console -v th:convert-ohlcv --weekly --monthly --quarterly data/source/y_universe.csv
```

Import the study formulas:
```bash
bin/console -v th:expression:import --file data/studies/mgwebgroup/formulas/sitb.csv
```

The watchlists for the study in csv format are already present in studies/mgwebgroup/watchlists/ folder and must be imported with the following command:
```bash
bin/console -v th:watchlist:import data/studies/mgwebgroup/watchlists/y_universe.csv y_universe
```
Watchlist named **y_universe** contains formulas necessary for market scoring (market survey).
