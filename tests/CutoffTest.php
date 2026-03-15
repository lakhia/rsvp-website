<?php

use PHPUnit\Framework\TestCase;

// Tests assume Config defaults: daily cutoff 21:00, advance_days=1,
// weekly cutoff Thursday 23:00, week start Monday.

class CutoffTest extends TestCase
{
    // --- cutoff_daily ---
    // Uses UTC timezone (set in bootstrap.php)

    public function test_daily_before_cutoff_returns_tomorrow(): void
    {
        // 20:00 UTC on 2026-03-14 — before 21:00 cutoff
        $now = mktime(20, 0, 0, 3, 14, 2026);
        $this->assertSame("2026-03-15", cutoff_daily($now));
    }

    public function test_daily_after_cutoff_returns_day_after_tomorrow(): void
    {
        // 22:00 UTC on 2026-03-14 — after 21:00 cutoff
        $now = mktime(22, 0, 0, 3, 14, 2026);
        $this->assertSame("2026-03-16", cutoff_daily($now));
    }

    public function test_daily_exactly_at_cutoff_is_treated_as_passed(): void
    {
        // 21:00:00 UTC — $now == $cutoff, not strictly greater, so NOT passed
        $now = mktime(21, 0, 0, 3, 14, 2026);
        $this->assertSame("2026-03-15", cutoff_daily($now));
    }

    public function test_daily_one_second_after_cutoff_is_passed(): void
    {
        $now = mktime(21, 0, 1, 3, 14, 2026);
        $this->assertSame("2026-03-16", cutoff_daily($now));
    }

    // --- cutoff_weekly ---
    // March 2026: Mon=9, Tue=10, Wed=11, Thu=12, Fri=13, Sat=14, Mon=16

    public function test_weekly_tuesday_before_thursday_cutoff_returns_next_monday(): void
    {
        // Tuesday March 10, 15:00 — Thursday cutoff not yet reached
        $now = new DateTime("2026-03-10 15:00:00");
        $this->assertSame("2026-03-16", cutoff_weekly($now));
    }

    public function test_weekly_thursday_before_cutoff_time_returns_next_monday(): void
    {
        // Thursday March 12, 22:00 — cutoff is 23:00 same day, not yet passed
        $now = new DateTime("2026-03-12 22:00:00");
        $this->assertSame("2026-03-16", cutoff_weekly($now));
    }

    public function test_weekly_thursday_after_cutoff_time_pushes_one_week(): void
    {
        // Thursday March 12, 23:30 — cutoff passed
        $now = new DateTime("2026-03-12 23:30:00");
        $this->assertSame("2026-03-23", cutoff_weekly($now));
    }

    public function test_weekly_friday_after_cutoff_day_pushes_one_week(): void
    {
        // Friday March 13, 15:00 — Thursday cutoff already passed
        $now = new DateTime("2026-03-13 15:00:00");
        $this->assertSame("2026-03-23", cutoff_weekly($now));
    }

    public function test_weekly_monday_returns_following_monday(): void
    {
        // Monday March 9, 10:00 — cutoff day (Thursday) is coming up
        $now = new DateTime("2026-03-09 10:00:00");
        $this->assertSame("2026-03-16", cutoff_weekly($now));
    }
}
