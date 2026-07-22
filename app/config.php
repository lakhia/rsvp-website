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

    // Available thaali sizes (ordered smallest to largest)
    const THAALI_SIZES = ["XS", "SM", "MD", "LG", "XL"];

    // Serving ratio per size relative to MD (1.0)
    const THAALI_RATIOS = [0.25, 0.5, 1.0, 1.5, 2.0];

    // Returns ['XS' => 0.25, 'SM' => 0.5, ...] — derives from THAALI_SIZES + THAALI_RATIOS
    public static function sizes(): array {
        static $cache = null;
        if ($cache === null) {
            $cache = array_combine(self::THAALI_SIZES, self::THAALI_RATIOS);
        }
        return $cache;
    }

    // Size selection mode: "any", "downgrade_only", "plus_minus_one"
    // "any": Users can select any size
    // "downgrade_only": Users can only select sizes <= their default size
    // "plus_one": Users can select 1 size above and all sizes below
    // "plus_minus_one": Users can select 1 size above or below default size
    const SIZE_SELECTION_MODE = "plus_one";

    // Export / Labels Configuration
    // ------------------------------

    // When true, CSV export and label generation cover the full week containing
    // the requested date. When false, only the single requested date is used.
    const DOWNLOAD_WEEK_RANGE = true;
}

?>
