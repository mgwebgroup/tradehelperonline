How to download traded symbols for each exchange. These instructions come from [this post](https://quant.stackexchange.com/questions/1640/where-to-download-list-of-all-common-stocks-traded-on-nyse-nasdaq-and-amex).
1. Login to NASDAQ's FTP server:
```bash
lftp ftp.nasdaqtrader.com
cd SymbolDirectory
get nasdaqlisted.txt
get otherlisted.txt
```
2. Legend for the columns in both files can be found [here](http://www.nasdaqtrader.com/trader.aspx?id=symboldirdefs). But I am going to quote it here:
nasdaqlisted.txt:
Market Category:
Q = NASDAQ Global Select MarketSM
G = NASDAQ Global MarketSM
S =  NASDAQ Capital Market

otherlisted.txt:
Exchange:
A = NYSE MKT
N = New York Stock Exchange (NYSE)
P = NYSE ARCA
Z = BATS Global Markets (BATS)
V = Investors' Exchange, LLC (IEXG)
