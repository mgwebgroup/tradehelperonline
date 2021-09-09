<?php
/*
* Will output Trading days formatted as YYYY-MM-DD, each on its own line, ho header
* Usage:
* shell> php ../assets/scripts/T_Calendar/trading_calendar.php --days=3 --direction=-1 --start=2021-08-31 2>/dev/null
*/

require __DIR__ . '/../../../vendor/autoload.php';

use App\Service\Exchange\DailyIterator;
use App\Service\Exchange\Equities\TradingCalendar;

$dates_begin = 946684800;
$dates_end = 4133894400;

$longOptions = ['days:', 'direction:', 'start:'];
$options = getopt('q:d:s:', $longOptions);
$usage = 'php trading_calendar.php --days=3 --direction=1 --start=2021-08-31';

try {
  if (!array_key_exists('days', $options)) { 
    throw new Exception('Please set --days option');
  } elseif (!is_numeric($options['days']))  {
    throw new Exception('Please set --days option');
  } else {
    $days = $options['days'];
  }
  if (!is_numeric($options['days'])) throw new Exception('Option --days needs to be numeric');
  if (array_key_exists('direction', $options)) {
    $direction = is_numeric($options['direction'])? $options['direction']: -1;
  } else {
    $direction = -1;
  }
  if (array_key_exists('start', $options)) {
    $startDate = new DateTime($options['start'], new DateTimeZone('America/New_York'));
  } else {
    throw new Exception('Need --start option');
  }
} catch (Exception $e) {
  fwrite(STDERR, $e->getMessage() . PHP_EOL);
  fwrite(STDERR, 'Example usage: '. $usage . PHP_EOL);
  exit(1);
}

$dailyIterator = new DailyIterator($dates_begin, $dates_end);
$dailyIterator->setStartDate($startDate);
$dailyIterator->setDirection($direction);
$tradingCalendar = new TradingCalendar($dailyIterator);

$i = 1;
foreach  ($tradingCalendar as $value ) {
  printf("%d,%s\n", $i, $value->format('Y-m-d'));
  $i++;
  $days--;
  if (!$days) break;
}

