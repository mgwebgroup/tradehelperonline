handleCalendar () {
  printf -v t_calendar_file $data_dir/trading_days_%s.csv $(date +%Y)
  if [ ! -f "${t_calendar_file}" ] ; then
    printf -v start "%(%Y)T-01-01" -1 
    php trading_calendar.php --start=${start} --direction=1 --days=253 2>/dev/null 1>$t_calendar_file
  fi
  
  # i contains ordinal number of trading day within calendar year
  i=$(sed -n -e "/$today/=" $t_calendar_file)
  if [ -z $i ] ; then
    echo "Could not find date $today in $t_calendar_file" > /dev/stderr
    exit 1
  fi
}
