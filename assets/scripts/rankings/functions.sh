handleCalendar () {
  # calendar_file will be dated with current year, i.e. trading_days_2021.csv, however the dates
  # inside will start from the previous year and end in the next. This is to not run out of 
  # dates on the border of current year. If you run out of dates, just erase the calendar file
  # and it will regenerate
  printf -v t_calendar_file $data_dir/trading_days_%s.csv $(date +%Y)
  if [ ! -f "${t_calendar_file}" ] ; then
    start="$(( $(date +%Y) - 1 ))-01-01"
    php trading_calendar.php --start=${start} --direction=1 --days=$(( 253*3 )) 2>/dev/null 1>$t_calendar_file
  fi
  
  # i contains ordinal number of trading day within calendar year
  i=$(sed -n -e "/$today/=" $t_calendar_file)
  if [ -z $i ] ; then
    echo "Could not find date $today in $t_calendar_file" > /dev/stderr
    exit 1
  fi
}
