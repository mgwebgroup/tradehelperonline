# Load symbols from y_universe and their associated sectors
ARGIND == 2 && FNR > 2 && /^[-A-Z]+/ {
#  print $1,$4
  sector_lookup[$1] = $4
}

# Load symbols from report for stocks
ARGIND == 4 && /^{{{ PERSISTING LDR/,/}}}/ {
  if ($0 !~ /^{{{ PERSISTING LDR/ && $0 !~ /}}}/) { 
    stock_PERSISTING_LDR[FNR] = $1
  }
}
ARGIND == 4 && /^{{{ PERSISTING X_UP/,/}}}/ {
  if ($0 !~ /^{{{ PERSISTING X_UP/ && $0 !~ /}}}/) { 
    stock_PERSISTING_X_UP[FNR] = $1
  }
}
ARGIND == 4 && /^{{{ PERSISTING X_DWN/,/}}}/ {
  if ($0 !~ /^{{{ PERSISTING X_DWN/ && $0 !~ /}}}/) { 
    stock_PERSISTING_X_DWN[FNR] = $1
  }
}
ARGIND == 4 && /^{{{ PERSISTING LGR/,/}}}/ {
  if ($0 !~ /^{{{ PERSISTING LGR/ && $0 !~ /}}}/) { 
    stock_PERSISTING_LGR[FNR] = $1
  }
}

# Load symbols from report for sectors
ARGIND == 5 && /^{{{ PERSISTING LDR/,/}}}/ {
  if ($0 !~ /^{{{ PERSISTING LDR/ && $0 !~ /}}}/) { 
    sector_PERSISTING_LDR[FNR] = $1
    print FNR, $1
  }
}
ARGIND == 5 && /^{{{ PERSISTING X_UP/,/}}}/ {
  if ($0 !~ /^{{{ PERSISTING X_UP/ && $0 !~ /}}}/) { 
    sector_PERSISTING_X_UP[FNR] = $1
  }
}
ARGIND == 5 && /^{{{ PERSISTING X_DWN/,/}}}/ {
  if ($0 !~ /^{{{ PERSISTING X_DWN/ && $0 !~ /}}}/) { 
    sector_PERSISTING_X_DWN[FNR] = $1
  }
}
ARGIND == 5 && /^{{{ PERSISTING LGR/,/}}}/ {
  if ($0 !~ /^{{{ PERSISTING LGR/ && $0 !~ /}}}/) { 
    sector_PERSISTING_LGR[FNR] = $1
  }
}

END {
  print "COLUMN,0"
  print "HED,PERSISTING LDR"
  for (i in sector_PERSISTING_LDR) {
    sector_symbol = sector_PERSISTING_LDR[i]
    printf "HED,%s\n", sector_symbol
    for (j in stock_PERSISTING_LDR) {
      stock_symbol = stock_PERSISTING_LDR[j]
      if (sector_lookup[stock_symbol] == sector_symbol ) {
	printf "DES,%s,STK,SMART/AMEX\n", stock_symbol 
      }
    }
  }

  print "COLUMN,1"
  print "HED,PERSISTING X_UP"
  for (i in sector_PERSISTING_X_UP) {
    sector_symbol = sector_PERSISTING_X_UP[i]
    printf "HED,%s\n", sector_symbol
    for (j in stock_PERSISTING_X_UP) {
      stock_symbol = stock_PERSISTING_X_UP[j]
      if (sector_lookup[stock_symbol] == sector_symbol ) {
	printf "DES,%s,STK,SMART/AMEX\n", stock_symbol 
      }
    }
  }

  print "COLUMN,2"
  print "HED,PERSISTING X_DWN"
  for (i in sector_PERSISTING_X_DWN) {
    sector_symbol = sector_PERSISTING_X_DWN[i]
    printf "HED,%s\n", sector_symbol
    for (j in stock_PERSISTING_X_DWN) {
      stock_symbol = stock_PERSISTING_X_DWN[j]
      if (sector_lookup[stock_symbol] == sector_symbol ) {
	printf "DES,%s,STK,SMART/AMEX\n", stock_symbol 
      }
    }
  }

  print "COLUMN,3"
  print "HED,PERSISTING LGR"
  for (i in sector_PERSISTING_LGR) {
    sector_symbol = sector_PERSISTING_LGR[i]
    printf "HED,%s\n", sector_symbol
    for (j in stock_PERSISTING_LGR) {
      stock_symbol = stock_PERSISTING_LGR[j]
      if (sector_lookup[stock_symbol] == sector_symbol ) {
	printf "DES,%s,STK,SMART/AMEX\n", stock_symbol 
      }
    }
  }
}

