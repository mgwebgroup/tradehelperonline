#! /bin/bash -
# Creates Market Survey watchlists based on reports for sectors and stocks for Interactive Brokers
#
# Prerequisites.
# These are files that must exist:
# * data/Market_Survey - symbolic link to a market survey file for the current month. It references which sector each stock belongs to. How to create it:
# This is example for month of September 2021. Save a csv file from 'Market Survey_2109.ods. Use column (":") as field separator, not comma (",")! Create symbolic link 'tradehelperonline/data/Market_Survey' to the .csv file.
# * assets/scripts/rankings/data/y_universe/SPYprcnt-1d/report_${DATE_REPORT} - stock report
# * assets/scripts/rankings/data/sectors/SELFprcnt-5d/report_${DATE_REPORT} - sector report
# Directory 'SPYprcnt-1d' is a calculation method used to determine ranks for stocks. At time of this writing, proved most effective, compared to others. 
# Directory 'SELFprcnt-5d' is a calc method for ranking of sectors and has been shown effective to determine market stance. This method has been used for 5 years by now. It is hard-coded into this script. 
# 
# CLI Usage:
# CALC_METHOD=SPYprcnt-1d
# DATE_REPORT=2021-08-02
# ./watchlist2 [$CALC_METHOD $DATE_REPORT]

# if no respective vars are exported to the script, set date and calc method to supplied args. If args are not supplied, set default calc method and today's date.
if [ ! -v CALC_METHOD ] ; then CALC_METHOD=${1:-"SPYprcnt-1d"} ; fi
if [ ! -v DATE_REPORT ] ; then DATE_REPORT=${2:-$(date +%Y-%m-%d)} ; fi

# Searches below are implemented for the typical case when this script is a symbolic link in tradehelperonline/data directory
project_root=/var/www/html/tradehelperonline

market_survey=$(find $project_root -name Market_Survey)
if [ ! -f $market_survey ] ; then 
  echo "Could not find file '$market_survey'"
  exit 1
fi
stock_report=$(find ${project_root}/assets/scripts/rankings/data/y_universe/${CALC_METHOD} -name report_${DATE_REPORT})
if [ ! -f $stock_report ] ; then
  echo "Could not find stock report in '$stock_report'"
  exit 1
fi
sector_report=$(find ${project_root}/assets/scripts/rankings/data/sectors/SELFprcnt-5d -name report_${DATE_REPORT})
if [ ! -f $sector_report ] ; then
  echo "Could not find sector report in '$sector_report'"
  exit 1
fi
awk_script=$(find ${project_root}/assets/scripts/rankings/get_watchlist.awk)
if [ ! -f $awk_script ] ; then
  echo "Could not find awk script in '$awk_script'"
  exit 1
fi

awk -f $awk_script FS=: $market_survey FS=, $stock_report $sector_report

