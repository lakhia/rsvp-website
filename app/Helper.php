<?php

class Helper
{
    // Build parts for a parameterized INSERT ... ON DUPLICATE KEY UPDATE query.
    // Returns [cols, placeholders, updates, types, values] where all entry
    // values are bound via bind_param — no values are interpolated into SQL.
    public static function dict_to_upsert_parts(array $dict): array
    {
        $keys = array_keys($dict);
        $cols = implode(", ", $keys);
        $placeholders = implode(", ", array_fill(0, count($keys), "?"));
        $updates = implode(", ", array_map(fn($k) => "$k = VALUES($k)", $keys));
        $types = str_repeat("s", count($keys));
        return [$cols, $placeholders, $updates, $types, array_values($dict)];
    }

    // Response via json contains data, message and date
    public static function print_to_json(
        mixed $data,
        ?string $msg,
        ?string $date = null,
        mixed $other = null,
    ): void {
        $response = [
            "msg" => $msg,
            "date" => $date,
            "data" => $data,
            "other" => $other,
        ];
        echo json_encode($response, JSON_NUMERIC_CHECK);
    }

    // Get beginning week date in mysql format
    public static function get_week(string $date, int $offset): string
    {
        if (!$date) {
            $date = new DateTime();
            $day = $date->format("w");
            if ($day == 6) {
                // Saturday is cutoff to show next week
                $day = -1;
            }
        } else {
            $date = DateTime::createFromFormat("Y-m-d", $date);
            $day = 1;
        }

        return self::get_offset($date, $offset + 1 - $day);
    }

    // Get day of interest in mysql format
    public static function get_day(int $offset): string
    {
        $date = new DateTime();
        return self::get_offset($date, $offset);
    }

    // Get if defined, otherwise return default
    public static function get_if_defined(mixed &$var, mixed $default = null): mixed
    {
        return isset($var) ? $var : $default;
    }

    // Get GET parameter by key, or return default
    public static function get_param(string $key, mixed $default = null): mixed
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    // Die with a JSON error message
    public static function json_error(string $msg): never
    {
        die(json_encode(["msg" => $msg]));
    }

    // Given a date and offset, return mysql date
    public static function get_offset(DateTime $date, int $offset): string
    {
        if ($offset >= 0) {
            $interval = "P" . $offset . "D";
        } else {
            $interval = "P" . -$offset . "D";
        }
        $interval = new DateInterval($interval);

        // PHP DateInterval doesn't like negative numbers
        if ($offset >= 0) {
            $date->add($interval);
        } else {
            $date->sub($interval);
        }
        return $date->format("Y-m-d");
    }
}
