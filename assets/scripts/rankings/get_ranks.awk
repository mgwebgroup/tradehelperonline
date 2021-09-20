# This script sorts symbols in descending order by their delta percent relative to SPY delta percent
# I.e., if SPY went 0.1% today, and GOOG went 1.1% today, diff value for GOOG will be 1.1%-0.1%=1%
# Closing Price of the OHLCV data is considered in the evaluation.
# Your OHLCV files must have a header line, contents of the header don't matter.
# How to run:
# shell> awk -F, -f THIS_SCIPT_NAME OHLCV_FOR_T.csv OHLCV_FOR_T-1.csv > rank_T.csv
# Note that -F, is necessary to designate field separator for csv files. This is important.
# Result (inside rank_T.csv file):
# 1,FB,100.148
# 2,GRPN,4.38737
# 3,APA,4.35782
# ... 

BEGIN {
  OFS = ","
}

{ 
  if ( ARGIND == 1 && FNR > 1 ) T_p[$1] = $5
  if ( ARGIND == 2 && FNR > 1 ) T1_p[$1] = $5
}

END {
  switch ( comp ) {
    case "SELFprcnt":
      diff_itself(T_p, T1_p)
      break  
    case "SPYabs":
    default:
      diff_Abs_SPY(T_p, T1_p)
      break
  }

# print in ascending order
  PROCINFO["sorted_in"] = "@val_num_asc"
  i = 1
  for ( symbol in diff ) {
    if ( diff[symbol] > 0 ) { 
#      diff_pos[symbol] = diff[symbol]
      print i ,symbol, diff[symbol]
      i++
      delete diff[symbol]
    }
  }
  PROCINFO["sorted_in"] = "@val_num_desc"
  i = -1
  for ( symbol in diff ) {
    if ( diff[symbol] <= 0 ) { 
#      diff_neg[symbol] = diff[symbol]
      print i, symbol, diff[symbol]
      i--
      delete diff[symbol]
    }
  }
}

function computeDeltaAbs(new, old) {
  return ( new - old )
}

function computeDeltaPrcnt(new, old) {
  return ( new - old ) / old * 100
}

function diff_Abs_SPY(T_p, T1_p) {
  if ( T_p["SPY"] == 0 ) { 
    print "No closing price for SPY in file for T" > "/dev/stderr"
    exit 1
  }
  if ( T1_p["SPY"] == 0 ) {
    print "No closing price for SPY in file for T-1" > "/dev/stderr"
    exit 1
  }
  delta["SPY"] = computeDeltaAbs( T_p["SPY"], T1_p["SPY"] )
  delete T_p["SPY"]
  delete T1_p["SPY"]
  for ( symbol in T_p ) {
    delta[symbol] = computeDeltaAbs( T_p[symbol], T1_p[symbol] )
    diff[symbol] = delta[symbol] - delta["SPY"]
##    printf "%s: delta %5.2f diff %5.2f\n", symbol, delta[symbol], diff[symbol]
  }
}

function diff_itself(T_p, T1_p) {
  for ( symbol in T_p ) {
    diff[symbol] = computeDeltaPrcnt(T_p[symbol], T1_p[symbol])
#    printf "%s: T_p[symbol]=%2.2f T1_p[symbol]=%2.2f diff[symbol]=%2.2f\n", symbol, T_p[symbol], T1_p[symbol], diff[symbol]
  }
}
