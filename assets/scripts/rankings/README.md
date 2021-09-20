## General

### Description:

This script uses php file *trading_calendar.php*, which creates a list of trading days (T's) for a given year. Make sure there is a symbolic link to that script present in this script's directory. 
The script takes candlestick (OHLCV) data csv files as its source data to perform analyses. These price files are stored in the *data/* directory. Results are stored in *data/<analysis_type>/* subdirectory, i.e. *data/SPYabs-1d/*. 
The script will download the price files from the prod server using its private key. The directory where it will look for the private key is: ~/.ssh/tradehelper-prod.pem. You can override this by specifying *-k path/to/PRIVATE_KEY* option. The script will connect to the prod database via SSH tunnel and will download price files for 13 days. You can specify a date with *-d YYYY-MM-DD* option, today's date is used if no date is specified.
```
# call script with
./main.sh -d 2021-09-03
# Price files will be downloaded into default data/ directory:
2021-09-03.csv
2021-09-02.csv
...
2021-08-13.csv
``` 

Analysis list (description):
In the formulas below *C* without index stands for a Closing price for trading day *T*. *C1* is a closing price for previous trading day *T-1*, and so on.
* For comparison method *SPYabs-1d* each stock symbol will be ranked according to absolute difference with SPY: C-C1 for a stock minus C-C1 for SPY. Each stock will then be ranked according to its absolute perfomance (in dollars) relative to the S&P Spider ETF.
* For comparison method *SELFprcnt-5d* each stock symbol will be ranked according to percent difference in closing price: delta = ( C - C4 ) / C4 * 100%. 

Ranks will be summarized and tokenized, resulting in *summary__YYYY-MM-DD.csv* files. Summary files show how ranks change over 5 T's and contain sum of ranks and their delta (direction) along with a token which relates sum to direction. Sum of ranks shows if a symbol is remaining among leaders/laggers consistentlyand is simply a sum of ranks over 5 T's. Consistent positive leading ranks will tend to give positive sums and vice versa. Delta (direction) takes rank for T and starting rank T-4 to determine direction. For example, if a symbol starts out in high ranks during T-4 and ends up in low ranks for T, direction will be negative. And finally, token characterizes relation of the sum to direction, i.e:

* Positive Sum, Negative Dir, TOKEN=X_DWN
* Negative Sum, Negative Dir, TOKEN=LGR
* Negative Sum, Positive Dir, TOKEN=X_UP
* Positive Sum, Positive Dir, TOKEN=LDR

For a trading day T a summary is prepared by looking at each symbol's rank for T through T-4. In this way, to prepare 1 summary 5 rank files are needed.

Eight (8) summary files are then analyzed for trends. The following trend situations are recognized:
 * NASCENT: only bullish or bearish token pairs (X_UP/LDR,  X_DWN/LGR) are present in at least 3 consecutive summary files, starting from T: T, T1, T2.
 * PERSISTING 5/8: Same token for direction is present 5 or more times during 8 consecutive T's in summaries, i,e. X_UP for same symbol is found 5 times in any of the summary files for for T, T1, ... T7
 * CONFIRMED LEADER/LAGGER/IN_TRANSIT: previous category, but the token is also present in at least 3 consecuitive summary files: T, T1, T2


## Usage

### Creation of Reports:

1. For symbols in watchlist 'y_universe' defined in 'query0' download price files, create ranks using default ranking algorithm, summarize them and determine trends. Use 'data/y_universe/SPYabs-1d/' directory. Default ranking algorithm compares daily closing deltas for the stock to that of the SPY.
```
DATE_REPORT=2021-08-31
./main.sh -d ${DATE_REPORT} -l data/y_universe -q query0 > data/y_universe/SPYabs-1d/report_${DATE_REPORT}

```

2. For symbols in watchlist 'sectors' defined in 'query1' perform same actions as in the previous example using ranking algorithm 'SELFprcnt-5d'. Use 'data/sectors/SELFprcnt-5d' directory. Ranking algorithm 'self' compares prices to themselves 5 T days ago.
```
DATE_REPORT:=2021-08-31
./main.sh -d ${DATE_REPORT} -l data/sectors -q query1 -c SELFprcnt-5d > data/sectors/SELFprcnt-5d/report_${DATE_REPORT}
```

### Back Tests.

1. Calculate min max price percentages over 10 T's for SPY that occurred during August 2021. Output will be on screen.
```
for DATE_BACKTEST in $(awk -F, '$0 ~ /^[[:digit:]]+,2021-08-[[:digit:]]+/ {print $2}' data/y_universe/trading_days_2021.csv) ; do \
echo -n "${DATE_BACKTEST}: " ; \
./backtest.sh -l data/y_universe -d $DATE_BACKTEST -n 10 -s SPY ; \
done
# Output (last two numbers are max and min percent price during 10 T's):
2021-08-02: SPY,2.14,0.00
2021-08-03: SPY,1.32,-0.49
...
2021-08-31: SPY,0.36,-1.64
```


2. Same as above, but save results to file ${DATE_BACKTEST}.csv. For each symbol in file
*tradehelperonline/data/source/y_universe.csv*
using price data in:
*data/y_universe/*.csv*
save results to directory 
*backtests/y_universe/*
```
for DATE_BACKTEST in $(awk -F, '$0 ~ /^[[:digit:]]+,2021-08-[[:digit:]]+/ {print $2}' data/y_universe/trading_days_2021.csv) ; do \
awk -F, 'NR > 1 {print $1}' /var/www/html/tradehelperonline/data/source/y_universe.csv | xargs -n1 ./backtest.sh -l data/y_universe -d ${DATE_BACKTEST} -s > backtests/y_universe/${DATE_BACKTEST}.csv ; \
done
```


3. Same as above, but figure min/max price fluctuations over 10 T's for symbols categorized as PERSISTING X_UP in data/y_universe/SPYabs-1d/report\* file. 
Save results in 'backtests/SPYabs-1d/PERSISTING_X_UP/${DATE_BACKTEST}.csv':
```
for DATE_BACKTEST in $(awk -F, '$0 ~ /^[[:digit:]]+,2021-08-[[:digit:]]+/ {print $2}' data/y_universe/trading_days_2021.csv) ; do \
awk -F, '/{{{ PERSISTING X_UP/,/}}}/ {print $1}' data/y_universe/SPYabs-1d/report_${DATE_BACKTEST} | sed -e '/\({{{\)\|\(}}}\)/d' | xargs -n1 ./backtest.sh -l data/y_universe -d ${DATE_BACKTEST} -s > backtests/SPYabs-1d/PERSISTING_X_UP/${DATE_BACKTEST}.csv ; \
done
```

4. Same as above, but narrow the test base to PERSISTING X_UP CONFIRMED symbols.
```
for DATE_BACKTEST in $(awk -F, '$0 ~ /^[[:digit:]]+,2021-08-[[:digit:]]+/ {print $2}' data/y_universe/trading_days_2021.csv) ; do \
awk -F, '/{{{ PERSISTING X_UP/,/}}}/ { if ($4 == "YES" ) print $1 }' data/y_universe/SPYabs-1d/report_${DATE_BACKTEST} | sed -e '/\({{{\)\|\(}}}\)/d' | xargs -n1 ./backtest.sh -l data/y_universe -d ${DATE_BACKTEST} -s > backtests/SPYabs-1d/PERSISTING_X_UP_CONF/${DATE_BACKTEST}.csv ; \
done
```

5. Same as item 3, but use category NASCENT UP.
```
for DATE_BACKTEST in $(awk -F, '$0 ~ /^[[:digit:]]+,2021-08-[[:digit:]]+/ {print $2}' data/y_universe/trading_days_2021.csv) ; do \
awk -F, '/{{{ NASCENT UP/,/}}}/ {print $1}' data/y_universe/SPYabs-1d/report_${DATE_BACKTEST} | sed -e '/\({{{\)\|\(}}}\)/d' | xargs -n1 ./backtest.sh -l data/y_universe -d ${DATE_BACKTEST} -s > backtests/SPYabs-1d/NASCENT_UP/${DATE_BACKTEST}.csv ; \
done
```

6. Same as item 3, but use category PERSISTING LDR.
```
for DATE_BACKTEST in $(awk -F, '$0 ~ /^[[:digit:]]+,2021-08-[[:digit:]]+/ {print $2}' data/y_universe/trading_days_2021.csv) ; do \
awk -F, '/{{{ PERSISTING LDR/,/}}}/ {print $1}' data/y_universe/SPYabs-1d/report_${DATE_BACKTEST} | sed -e '/\({{{\)\|\(}}}\)/d' | xargs -n1 ./backtest.sh -l data/y_universe -d ${DATE_BACKTEST} -s > backtests/SPYabs-1d/PERSISTING_LDR/${DATE_BACKTEST}.csv ; \
done
```

7. Same as above, but narrow the test sample to PERSISTING LDR CONF symbols.
```
for DATE_BACKTEST in $(awk -F, '$0 ~ /^[[:digit:]]+,2021-08-[[:digit:]]+/ {print $2}' data/y_universe/trading_days_2021.csv) ; do \
awk -F, '/{{{ PERSISTING LDR/,/}}}/ { if ($4 == "YES" ) print $1}' data/y_universe/SPYabs-1d/report_${DATE_BACKTEST} | sed -e '/\({{{\)\|\(}}}\)/d' | xargs -n1 ./backtest.sh -l data/y_universe -d ${DATE_BACKTEST} -s > backtests/SPYabs-1d/PERSISTING_LDR_CONF/${DATE_BACKTEST}.csv ; \
done
```

If you have reports for additional calculation methods, you should be able to replace directory name in the above examples. I.e.: data/y_universe/SPYabs-1d, replace with: data/y_universe/SPYprcnd-5d.

