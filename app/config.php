<?php

/**
 * Application Configuration
 */

class Config
{
    // Cutoff Configuration
    // --------------------

    // Cutoff mode: "daily" or "weekly"
    const CUTOFF_MODE = "daily";

    // Timezone for date/time calculations (PHP timezone identifier)
    const TIMEZONE = "America/Los_Angeles";

    // Weekly Mode Settings
    // Used when CUTOFF_MODE = "weekly"
    const WEEKLY_CUTOFF_DAY = "Thursday";    // Day of week when cutoff occurs
    const WEEKLY_CUTOFF_TIME = "23:00";       // Time on that day (HH:MM)
    const WEEKLY_WEEK_START = "Monday";       // First day of meal week

    // Daily Mode Settings
    // Used when CUTOFF_MODE = "daily"
    const DAILY_CUTOFF_TIME = "21:00";        // Daily cutoff time (HH:MM)
    const DAILY_ADVANCE_DAYS = 1;             // Days in advance cutoff applies

    // Size Selection Configuration
    // ----------------------------

    // Available thaali sizes (comma-separated, smallest to largest)
    const THAALI_SIZES = ["XS", "SM", "MD", "LG", "XL"];

    // Size selection mode: "any", "downgrade_only", "plus_minus_one"
    // "any": Users can select any size
    // "downgrade_only": Users can only select sizes <= their default size
    // "plus_one": Users can select 1 size above and all sizes below
    // "plus_minus_one": Users can select 1 size above or below default size
    const SIZE_SELECTION_MODE = "plus_one";
}

?>
