$cutoff_time = Config::DAILY_CUTOFF_TIME;
$advance_days = Config::DAILY_ADVANCE_DAYS;

$cutoff = strtotime('today ' . $cutoff_time);
$now = time();

// If we've passed today's cutoff, add an extra day
$days_ahead = $advance_days + (($now > $cutoff) ? 1 : 0);

return date('Y-m-d', strtotime('+' . $days_ahead . ' day'));
