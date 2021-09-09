## Usage

### How it works:

This script uses php file *trading_calendar.php*, which creates a list of trading days (T's) for a given year. Make sure there is a symbolic link to that script present in this script's directory. Candlestick (OHLCV) data files are needed for this script to work and do its analysis. All source and output data files are stored in the default *data/* directory. The script will download the price files from the prod server using its private key. The directory where it will look for the private key is: ~/.ssh/tradehelper-prod.pem. You can override this by specifying *-k PRIVATE_KEY* option. The script will connect to the prod database via SSH tunnel and will download price files for 13 days. You can specify a date with *-d YYYY-MM-DD* option, today's date is used if no date is specified.
```
# call script with
./main.sh -d 2021-09-03
# Price files will be downloaded into default data/ directory:
2021-09-03.csv
2021-09-02.csv
...
2021-08-13.csv
``` 

For comparison method *SPY* each stock symbol will be ranked according to absolute difference with SPY: delta = C-C1 for a stock minus C-C1 for the SPY. Each stock will then be ranked according to its absolute perfomance relative to the S&P Spider ETF.
For comparison method *self* each stock symbol will be ranked according to percent difference in closing price: delta = ( C - C4 ) / C4 * 100%. 

Ranks will be summarized and tokenized, resulting in 8 *summary__YYYY-MM-DD.csv* files. For a trading day T a summary is prepared by looking at each symbol's rank for T through T-4. In this way, to prepare 1 summary 5 ranks are needed. Again, rank files can rank between T and T-1 (SPY method) OR between T and T-4 (self method).

Summary files are then analyzed for trends. The following trend situations are recognized:
 * NASCENT: only bullish or bearish token pairs (X_UP/LDR,  X_DWN/LGR) are present in at least 3 consecutive summary files, starting from T: T, T1, T2.
 * PERSISTING 5/8: Same token for direction is present 5 or more times during 8 consecutive T's in summaries, i,e. X_UP for same symbol is found 5 times in any of the summary files for for T, T1, ... T7
 * CONFIRMED LEADER/LAGGER/IN_TRANSIT: previous category, but the token is also present in at least 3 consecuitive summary files: T, T1, T2


### Examples:

1. For symbols in watchlist 'y_universe' defined in file 'query0' download price files, create ranks using default ranking algorithm, summarize them and determine trends. Use 'data/y_universe' directory. Default ranking algorithm compares daily closing deltas for the stock to that of the SPY.
```
./main.sh -d 2021-08-31 -l data/y_universe -q query0 > data/y_universe/report_2021-08-31

```

2. For symbols in watchlist 'sectors' defined in file 'query1' perform same actions as in the previous example using ranking algorithm 'self'. Use 'data/sectors' directory. Ranking algorithm 'self' compares prices to tthemselves 5 T days ago.
```
./main.sh -d 2021-08-31 -l data/sectors -q query1 -c self > data/sectors/report_2021-08-31
```


