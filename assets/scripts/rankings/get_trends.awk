# Script to figure out trends using tokenized summaries.
# shell> awk -F, -f THIS_FILE rank_T rank_T[1-7].csv > trends_T.csv
# Needs 8 summaries to work with. 
# Types of trends recognized:
#   * NASCENT: only bullish or bearish token pairs (X_UP/LDR,  X_DWN/LGR) are present in at least 3 consecutive summary files, starting from T: T, T1, T2.
#   * LEADING: Same token for direction is present 5 or more times during 8 consecutive T's, i,e. X_UP for same symbol is found 5 times in any of the summary files for for T, T1, ... T7
#   * CONFIRMED LEADER/LAGGER/IN_TRANSIT: previous category, but is present in at least 3 consecuitive summary files: T, T1, T2

BEGIN {
  OFS = ","
  print "symbol,nascent,leading,conf_leading" 
}

{ 
  if ( ARGIND == 1 ) summ_T[$1] = $4
  if ( ARGIND == 2 ) summ_T1[$1] = $4
  if ( ARGIND == 3 ) summ_T2[$1] = $4
  if ( ARGIND == 4 ) summ_T3[$1] = $4
  if ( ARGIND == 5 ) summ_T4[$1] = $4
  if ( ARGIND == 6 ) summ_T5[$1] = $4
  if ( ARGIND == 7 ) summ_T6[$1] = $4
  if ( ARGIND == 8 ) summ_T7[$1] = $4
}

END {
  split("LDR,X_UP,X_DWN,LGR", token, ",")
  PROCINFO["sorted_in"] = "@ind_str_asc"
  for ( symbol in summ_T ) {
    nascent_up = nascent_dwn = 0
    if ( summ_T[symbol] == "X_UP" || summ_T[symbol] == "LDR" ) nascent_up++
    if ( summ_T1[symbol] == "X_UP" || summ_T1[symbol] == "LDR" ) nascent_up++
    if ( summ_T2[symbol] == "X_UP" || summ_T2[symbol] == "LDR" ) nascent_up++
    if ( summ_T[symbol] == "X_DWN" || summ_T[symbol] == "LGR" ) nascent_dwn++
    if ( summ_T1[symbol] == "X_DWN" || summ_T1[symbol] == "LGR" ) nascent_dwn++
    if ( summ_T2[symbol] == "X_DWN" || summ_T2[symbol] == "LGR" ) nascent_dwn++
    nascent = "N/A";
    if ( nascent_up >= 3 ) { 
      nascent = "UP" 
    } 
    if ( nascent_dwn >= 3 )  {
      nascent = "DWN"
    }

    for (i in token) {
      leading = "N/A"
      count = 0
      if ( summ_T[symbol] == token[i] ) count++
      if ( summ_T1[symbol] == token[i] ) count++
      if ( summ_T2[symbol] == token[i] ) count++
      if ( summ_T3[symbol] == token[i] ) count++
      if ( summ_T4[symbol] == token[i] ) count++
      if ( summ_T5[symbol] == token[i] ) count++
      if ( summ_T6[symbol] == token[i] ) count++
      if ( summ_T7[symbol] == token[i] ) count++
      if ( count >= 5) {
        leading = token[i]
        break
      } 
    }

    for (i in token) {
      conf_leading = "N/A"
      if ( leading == token[i]  ) {
        conf_leading = "NO"
        count = 0
        if ( summ_T[symbol] == token[i] ) count++
        if ( summ_T1[symbol] == token[i] ) count++
        if ( summ_T2[symbol] == token[i] ) count++
        if ( count >= 3) {
          conf_leading = "YES"
          break
        } 
      }
    }
    
    print symbol, nascent, leading, conf_leading

  }
}
