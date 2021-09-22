#! /bin/bash -
# Creates Market Survey watchlists for Interactive Brokers
#
# Usage:
# Files:
# * data/Market_Survey - symbolic link to a market survey file for the current month. It references which sector each stock belongs to. How to creat it:
# Save MARKET_SURVEY.csv from the spreadsheet format into csv. USE column (":") as field separator, not comma (",")! Create symbolic link Market_Survey to the .csv file.
# * assets/scripts/rankings/data/y_universe/SPYprcnt-1d/report_${DATE_REPORT} - stock report
# * assets/scripts/rankings/data/sectors/SELFprcnt-5d/report_${DATE_REPORT} - sector report
# run:
# CALC_METHOD=SPYprcnt-1d
# DATE_REPORT=2021-08-02
# ./watchlist2 [$CALC_METHOD $DATE_REPORT]

# if no respective vars are exported to the script, set to supplied args, and if args are not supplied, set default calc method and today's date
if [ ! -v CALC_METHOD ] ; then CALC_METHOD=${1:-"SPYprcnt-1d"} ; fi
if [ ! -v DATE_REPORT ] ; then DATE_REPORT=${2:-$(date +%Y-%m-%d)} ; fi

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

