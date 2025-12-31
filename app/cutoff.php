<?php

function cutoff_weekly()
{
    $cutoff_day = Config::WEEKLY_CUTOFF_DAY;
    $cutoff_time = Config::WEEKLY_CUTOFF_TIME;
    $week_start = Config::WEEKLY_WEEK_START;

    $now = new DateTime();
    $day_of_week = $now->format("w"); // 0=Sun, 1=Mon, ..., 6=Sat

    // Convert cutoff day name to number for comparison
    $days_map = [
        "Sunday" => 0,
        "Monday" => 1,
        "Tuesday" => 2,
        "Wednesday" => 3,
        "Thursday" => 4,
        "Friday" => 5,
        "Saturday" => 6,
    ];
    $cutoff_day_num = $days_map[$cutoff_day];

    // Find this week's cutoff datetime
    if ($day_of_week == $cutoff_day_num) {
        // Today is the cutoff day
        $cutoff_datetime = clone $now;
    } elseif ($day_of_week > $cutoff_day_num) {
        // This week's cutoff day already passed (e.g., today is Fri, cutoff is Thu)
        $cutoff_datetime = new DateTime("last " . $cutoff_day);
    } else {
        // This week's cutoff day is coming up (e.g., today is Mon, cutoff is Thu)
        $cutoff_datetime = new DateTime("this " . $cutoff_day);
    }

    // Set the cutoff time
    [$hour, $minute] = explode(":", $cutoff_time);
    $cutoff_datetime->setTime((int) $hour, (int) $minute, 0);

    // Calculate the next editable week start
    $next_week_start = new DateTime("next " . $week_start);

    // If we've passed this week's cutoff, push to the following week
    if ($now >= $cutoff_datetime) {
        $next_week_start->modify("+1 week");
    }

    return $next_week_start->format("Y-m-d");
}

function cutoff_daily()
{
    $cutoff_time = Config::DAILY_CUTOFF_TIME;
    $advance_days = Config::DAILY_ADVANCE_DAYS;

    $cutoff = strtotime("today " . $cutoff_time);
    $now = time();

    // If we've passed today's cutoff, add an extra day
    $days_ahead = $advance_days + ($now > $cutoff ? 1 : 0);

    return date("Y-m-d", strtotime("+" . $days_ahead . " day"));
}
