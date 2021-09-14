#! /bin/bash

data_dir=data
symbol=
days=10

. ./functions.sh

n=0
while getopts "l:s:n:d:" option ; do
  case $option in 
    l)
      if [ ! -d $OPTARG ] ; then 
        echo "Directory $OPTARG does not exist"
        exit 1
      fi
      data_dir=$OPTARG
    ;;
    s)
      symbol=$OPTARG
    ;;
    n)
      days=$OPTARG
    ;;
    d)
      today=$OPTARG
    ;;
  esac
  (( n++ ))
done
shift $(( n * 2 ))

handleCalendar

mapfile -t lines < <(sort -n -k1,1 -t, $t_calendar_file | sed -n -e "/^$i/,+${days}p")
#echo ${#lines[@]}
#exit
for line in ${lines[@]} ; do 
  n=${line%,*}
  F[$n]="$data_dir/${line#*,}.csv"
#  echo $n = ${F[$n]}
  if [ ! -f ${F[$n]} ] ; then
    echo "Could not find price file ${T[$n]}.csv"
    exit 
  fi
done

awk -F, '{ if (s == $1) { if (ARGIND == 2) { p=$5; max=$5; min=$5 } else { if ($5 > max) { max=$5 } if ($5 < min ) { min=$5 } }}} END { if ( p > 0 ) printf "%s,%3.2f,%3.2f\n", s, (max-p)/p*100, (min-p)/p*100 }' s=$symbol "${F[@]}"

