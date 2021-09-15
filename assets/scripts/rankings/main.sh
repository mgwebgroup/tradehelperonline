#! /bin/bash 
data_dir=data
query_file=query0
comp_var="comp=SPY"
config_file=./config

if [ ! -f $config_file ] ; then
  echo "Missing config file"
  exit 1
fi
. $config_file

. ./functions.sh

printf -v today "%(%F)T" -1
declare -i tunnel

while getopts "d:k:l:q:c:" option ; do
  case $option in 
    d) 
      today=$OPTARG
    ;;
    k)
      prod_key_location=$OPTARG
    ;;
    l)
      if [ ! -d $OPTARG ] ; then 
        mkdir $OPTARG
      fi
      data_dir=$OPTARG
    ;;
    q)
      if [ -f $OPTARG ] ; then
        query_file=$OPTARG
      fi
    ;;
    c)
      if (( c == "self" )) ; then comp_var="comp=self" ; fi
    ;;
    \?)
    ;;
  esac
done

if [ ! -f $query_file ] ; then
  echo "Missing file '$query_file'. Make sure it is present. Cannot continue." > /dev/stderr
  exit 1
fi 
query_template=$(sed -e "/^#/d" $query_file)

if [ ! -f $prod_key_location ] ; then
  echo "Could not find private key to access production server" > /dev/stderr
  exit 1
fi

handleCalendar

# download ohlcv data for 18 past Ts, get missing files only
mapfile -t lines < <(sort -r -n -k1,1 -t, $t_calendar_file | sed -n -e "/^$i/,+17p" )
for line in ${lines[@]} ; do 
  n=${line%,*}
  T[$n]=${line#*,}
#  echo $n = ${T[$n]}
  if [ ! -f "$data_dir/${T[$n]}.csv" ] ; then
    if [ -z $tunnel ] ; then
      DATABASE_URL=$(ssh -i $prod_key_location $prod_user@$prod_server_ip cat /var/www/html/.env | sed -n -e '/^DATABASE_URL/p')
      db_pass=${DATABASE_URL#DATABASE_URL=\"mysql://*:}
      db_pass=${db_pass%@*\"}
      db_server=${DATABASE_URL#DATABASE_URL=\"*@}
      db_server=${db_server%:*\"}
      db_user=${DATABASE_URL#DATABASE_URL=\"mysql://}
      db_user=${db_user%%:*\"}
      db_name=${DATABASE_URL##DATABASE_URL=\"mysql://*/}
      db_name=${db_name%\"}
      # open SSH tunnel to prod DB from 127.0.0.1:3307 for 25 seconds
      ssh -f -L 3307:$db_server:3306 $prod_user@$prod_server_ip sleep 25
      tunnel=$!
      echo "Opened ssh tunnel to prod db. PID=$tunnel" > /dev/stderr
    fi 
    sql_statement=${query_template/<timestamp>/\"${T[$n]}\"}
    mysql -C -u$db_user -p$db_pass -h127.0.0.1 -P3307 -D$db_name -B -e "${sql_statement}" | tr "\t" "," > "$data_dir/${T[$n]}.csv"
    if [ -s "$data_dir/${T[$n]}.csv" ] ; then echo "Downloaded prices into $data_dir/${T[$n]}.csv" > /dev/stderr ; fi 
  fi
done
tunnel= 

# Prepare rank files
n=$i
echo -n "Ranking..." > /dev/stderr
while (( n > ( i - 12 ) )) ; do 
  case $comp_var in 
    comp=SPY)
      awk -F, -f get_ranks.awk -v $comp_var "$data_dir/${T[$n]}.csv" "$data_dir/${T[(( n-1 ))]}.csv" > "$data_dir/rank_${T[$n]}.csv"
    ;;
    comp=self)
      awk -F, -f get_ranks.awk -v $comp_var "$data_dir/${T[$n]}.csv" "$data_dir/${T[(( n-5 ))]}.csv" > "$data_dir/rank_${T[$n]}.csv"
    ;;
  esac

  (( n-- ))
done
echo "done" > /dev/stderr

# Compute 8 summaries for 5 T's each and tokenize them:
n=$i
echo -n "Creating summaries..." > /dev/stderr
while (( n > ( i-8 ) )) ; do
  awk -F, -f tokenize_ranks.awk \
    "$data_dir/rank_${T[(( n ))]}.csv" \
    "$data_dir/rank_${T[(( n-1 ))]}.csv" \
    "$data_dir/rank_${T[(( n-2 ))]}.csv" \
    "$data_dir/rank_${T[(( n-3 ))]}.csv" \
    "$data_dir/rank_${T[(( n-4 ))]}.csv" \
      > "$data_dir/summary_${T[(( n ))]}.csv"
  (( n-- ))
done
echo "done" > /dev/stderr

# Identify trends 
n=$i
echo -n "Identifying trends..." > /dev/stderr
awk -F, -f get_trends.awk \
  "$data_dir/summary_${T[(( n ))]}.csv" \
  "$data_dir/summary_${T[(( n-1 ))]}.csv" \
  "$data_dir/summary_${T[(( n-2 ))]}.csv" \
  "$data_dir/summary_${T[(( n-3 ))]}.csv" \
  "$data_dir/summary_${T[(( n-4 ))]}.csv" \
  "$data_dir/summary_${T[(( n-5 ))]}.csv" \
  "$data_dir/summary_${T[(( n-6 ))]}.csv" \
  "$data_dir/summary_${T[(( n-7 ))]}.csv" \
    > "$data_dir/trends_${T[(( n ))]}.csv"
echo "done" > /dev/stderr

# Print results
echo
sed -n -e "/^\([A-Z]\{1,5\}\),UP/p" "$data_dir/trends_${T[(( n ))]}.csv" > temp
echo "{{{ NASCENT UP ($(sed -ne '$=' temp )): ***"
cat temp
echo }}}
echo

sed -n -e "/^\([A-Z]\{1,5\}\),[UPDWN/A]*,\(LDR\)/p" "$data_dir/trends_${T[(( n ))]}.csv" > temp
echo "{{{ PERSISTING LDR 5/8 ($(sed -ne '$=' temp )): ***"
cat temp
echo }}}
echo

sed -n -e "/^\([A-Z]\{1,5\}\),[UPDWN/A]*,\(X_UP\)/p" "$data_dir/trends_${T[(( n ))]}.csv" > temp
echo "{{{ PERSISTING X_UP 5/8 ($(sed -ne '$=' temp )): ***"
cat temp
echo }}}
echo

sed -n -e "/^\([A-Z]\{1,5\}\),[UPDWN/A]*,\(X_DWN\)/p" "$data_dir/trends_${T[(( n ))]}.csv" > temp
echo "{{{ PERSISTING X_DWN 5/8 ($(sed -ne '$=' temp )): ***"
cat temp
echo }}}
echo

sed -n -e "/^\([A-Z]\{1,5\}\),[UPDWN/A]*,\(LGR\)/p" "$data_dir/trends_${T[(( n ))]}.csv" > temp
echo "{{{ PERSISTING LGR 5/8 ($(sed -ne '$=' temp )): ***"
cat temp
echo }}}
echo

sed -n -e "/^\([A-Z]\{1,5\}\),DWN/p" "$data_dir/trends_${T[(( n ))]}.csv" > temp
echo "{{{ NASCENT DWN ($(sed -ne '$=' temp )): ***"
cat temp
echo }}}
echo

rm temp
