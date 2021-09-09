# Script to figure sum ranks and gradients using historical ranks
# shell> awk -F, -f THIS_FILE rank_T rank_T1.csv rank_T2.csv rank_T3.csv rank_T4.csv > summary_T.csv
# Will figure out sums ranks and gradients up to 5 files, as well as will put token for current direction.
BEGIN {
  OFS = ","
}

{ 
  if ( ARGIND == 1 ) rank_T[$2] = $1
  if ( ARGIND == 2 ) rank_T1[$2] = $1
  if ( ARGIND == 3 ) rank_T2[$2] = $1
  if ( ARGIND == 4 ) rank_T3[$2] = $1
  if ( ARGIND == 5 ) rank_T4[$2] = $1
}

END {
  PROCINFO["sorted_in"] = "@val_str_asc"
  for ( symbol in rank_T ) {
    sum = rank_T[symbol] + rank_T1[symbol] + rank_T2[symbol] + rank_T3[symbol] + rank_T4[symbol]
    gradient = rank_T[symbol] - rank_T4[symbol]

    if ( sum > 0 && gradient >= 0 ) token = "LDR"
    if ( sum > 0 && gradient < 0 )  token = "X_DWN"
    if ( sum < 0 && gradient > 0 ) token = "X_UP"
    if ( sum < 0 && gradient <= 0 )  token = "LGR"
    if ( sum == 0 )                 token = "N/A"

   print symbol, sum, gradient, token 
  }
}
