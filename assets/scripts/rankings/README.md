## General

### Description:

Script titled *main.sh* uses php file *trading_calendar.php*, which creates a list of trading days (T's) for a given year. Make sure a symbolic link to the php script is present in this script's directory. 
Script *main.sh* takes candlestick (OHLCV) csv files as its source data to perform analyses. These files are stored in the *data/* subdirectory. Results are stored in *data/ANALYSIS_METHOD/* subdirectory, i.e. *data/SPYabs-1d/*. If a price file, which is needed for analysis is missing, it will be created by downloading the price data from prod server using its private key. All configuration settings are stored in the *config* file, including where to look for the private key. You can override this by specifying *-k path/to/PRIVATE_KEY* option. The script will connect to the prod database via SSH tunnel.

Analysis Methods:
In the formulas below *C* without index stands for a Closing price for trading day *T*. *C1* is a closing price for previous trading day *T-1*, and so on. Cursive values below are substitued for the ANALYSIS_METHOD:
* *SPYabs-1d*: each stock symbol will be ranked according to absolute difference with SPY: C-C1 for a stock minus C-C1 for SPY. Each stock will then be ranked according to its absolute perfomance (in dollars) relative to the S&P Spider ETF.
* *SELFprcnt-5d* (default): each stock symbol will be ranked according to percent difference in closing price: delta = ( C - C4 ) / C4 * 100%. 
* *SPYprcnt-1d*: first, percent change in SPY over 1 T is calculated. Let's call it delta (SPY). Same is done for each stock symbol. Let's call it delta(symbol). Result is calculated as delta(symbol) - delta(SPY), and represents percent difference in price for a symbol over same for SPY. 

Results of analysis methods are ranked and stored in file *data/ANALYSIS_METHOD/rank_YYYY-MM-DD*. All of the methods require price files for at least 2 T's. 

Ranks will be summarized and tokenized, resulting in *summary__YYYY-MM-DD.csv* files. Summary files show how ranks for each symbol change over 5 T's. They contain sum of ranks and their delta (direction) along with a token which relates sum to direction. Sum of ranks shows if a symbol is remaining among leaders/laggers consistently, i.e. if it remains in high ranks relative to others, you obviously have a leader. Consistent positive leading ranks will tend to give positive sums and vice versa. Delta (direction) takes rank for T and starting rank T-4 to determine direction. For example, if a symbol starts out in high ranks during T-4 and ends up in low ranks for T, direction will be negative. And finally, token characterizes relation of the sum to direction, i.e:

* Positive Sum, Negative Dir, TOKEN=X_DWN
* Negative Sum, Negative Dir, TOKEN=LGR
* Negative Sum, Positive Dir, TOKEN=X_UP
* Positive Sum, Positive Dir, TOKEN=LDR

For a trading day T a summary is prepared by looking at each symbol's rank for T through T-4. In this way, to prepare one summary 5 rank files are needed.

Eight (8) summary files are then analyzed for trends. The following trend situations are recognized:
 * NASCENT: only bullish or bearish token pairs (X_UP/LDR,  X_DWN/LGR) are present in at least 3 consecutive summary files, starting from T: T, T1, T2.
 * PERSISTING 5/8: Same token for direction is present 5 or more times during 8 consecutive T's in summaries. Example: X_UP for same symbol is found 5 times in any of the summary files for for T, T1, ... T7
 * CONFIRMED LEADER/LAGGER/IN_TRANSIT: previous category, but the token is present in at least 3 consecuitive summary files: T, T1, T2


## Usage

### Creation of Reports:

All methods described here utilize *SPYabs-1d* calculation. If you want to change it, supply option -c SPYprcnt-1d and replace the SPYabs-1d directory to SPYprcnt-1d. For daily application of these commands, just use the portions inside the for loops to avoid recalculation for entire month.

1. For the month of August 2021, for symbols in watchlist 'y_universe' defined in default 'query0' download price files, create ranks using default ranking algorithm, summarize them and determine trends. Use 'data/y_universe/SPYabs-1d/' directory. Default ranking algorithm compares daily closing deltas for the stock to that of the SPY.
```
for DATE_REPORT in $(awk -F, '$0 ~ /^[[:digit:]]+,2021-08-[[:digit:]]+/ {print $2}' data/y_universe/trading_days_2021.csv) ; do \
echo ${DATE_REPORT} ; \
./main.sh -d ${DATE_REPORT} -l data/y_universe > data/y_universe/SPYabs-1d/report_${DATE_REPORT} ; \
done
```

Note default ranking algorithm is stored in the *config* file and can be changed via -c option. See next example.

2. For symbols in watchlist 'sectors' defined in 'query1' perform same actions as in the previous example using ranking algorithm 'SELFprcnt-5d'. Use 'data/sectors/SELFprcnt-5d' directory. Ranking algorithm 'self' compares prices to themselves 5 T days ago.
```
DATE_REPORT:=2021-08-31
./main.sh -d ${DATE_REPORT} -l data/sectors -q query1 -c SELFprcnt-5d > data/sectors/SELFprcnt-5d/report_${DATE_REPORT}
```


### Back Tests.

Back tests are designed to reveal performance over given period of time. The idea is very simple: determine min and max closing price over x number of T's. Results are output in the format of: SMBL,MAX_P,MIN_P as a separate line for each symbol.

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
'tradehelperonline/data/source/y_universe.csv'
using price data in:
'data/y_universe/YYYY-MM-DD.csv'
save results to directory 
'backtests/y_universe/'
```
for DATE_BACKTEST in $(awk -F, '$0 ~ /^[[:digit:]]+,2021-08-[[:digit:]]+/ {print $2}' data/y_universe/trading_days_2021.csv) ; do \
awk -F, 'NR > 1 {print $1}' /var/www/html/tradehelperonline/data/source/y_universe.csv | xargs -n1 ./backtest.sh -l data/y_universe -d ${DATE_BACKTEST} -s > backtests/y_universe/${DATE_BACKTEST}.csv ; \
done
```

3. Same as above, but figure min/max price fluctuations over 10 T's for symbols categorized as PERSISTING X_UP in data/y_universe/SPYabs-1d/report_YYYY-MM-DD file. 
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

Generated reports are then used for scoring of each token category (PERSISTING LDR, PERSISTING X_UP, etc.) for each analysis method and are summarized in spreadsheet files. Details of scoring are contained in the spreadsheet files.


### Watchlists

At time of this writing, generated reports for stocks and sectors are collected into watchlist for each T, which can be imported into Interactive Brokers (R). Script that does that is *watchlist2.sh*. It works with 3 files: 

1. Exported list of symbols and their respective sectors (Market_Survey - symbolic link)
2. data/y_universe/ANALYSIS_METHOD/report_YYYY-MM-DD - report for stocks
3. data/sectors/SELFprcnt-5d/report_YYYY-MM-DD - report for sectors.

The script takes sector in each token category (PERSISTENT LGR, PERSISTENT X_DWN, etc.) using sector report and then collects stocks with same tokens, like so:
* sector identified as PERSISTENT LDR:   stocks identified as PERSISTENT LDR, PERSISTENT X_UP
* sector identified as PERSISTENT X_UP: stocks identified as PERSISTENT X_UP, PERSISTENT LDR
* sector identified as PERSISTENT X_DWN: stocks identified as PERSISTENT X_DWN, PERSISTENT LGR
* sector identified as PERSISTENT LGR: stocks identified as PERSISTENT LGR, PERSISTENT X_DWN

How to run the script:
Create a symbolic link titled watchlist2 in *tradehelper/data* directory to 'tradehelper/assets/scripts/rankings/watchlist2.sh' first.
Then run the watchlist2 script:
```
./watchlist2 SPYprcnt-1d 2021-09-21 > watchlist.txt
```
You can omit both arguments at once, or only the second one. Default analysis method *SPYprcnt-1d* and default (today's) date will be used. Keep in mind that if you run this without arguments on a holiday or on a weekend, the scipt will fail.



