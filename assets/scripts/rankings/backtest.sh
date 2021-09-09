#! /bin/bash
data_dir=data

function T_calendar {
  echo "Positional params: " $@
  echo "date=$date"
  func_var="Func Var"
}

while getopts "l:" option ; do
  case $option in 
    l)
      if [ ! -d $OPTARG ] ; then 
        echo "Directory $OPTARG does not exist"
        exit 1
      fi
      data_dir=$OPTARG
    ;;
  esac
done

# Select symbols from the supplied report
params=($@)
date=${params[0]##*_}
T_calendar $data_dir
echo "func_var=$func_var" 

# Get date from the report and look at min/max closing prices over n T's

# Print results for symbol, max%, dd%


