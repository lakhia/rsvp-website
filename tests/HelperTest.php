<?php

use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    // -----------------------------------------------------------------------
    // get_offset
    // -----------------------------------------------------------------------

    public function test_get_offset_zero_returns_same_date(): void
    {
        $date = new DateTime("2026-03-15");
        $this->assertSame("2026-03-15", Helper::get_offset($date, 0));
    }

    public function test_get_offset_positive_adds_days(): void
    {
        $date = new DateTime("2026-03-15");
        $this->assertSame("2026-03-20", Helper::get_offset($date, 5));
    }

    public function test_get_offset_negative_subtracts_days(): void
    {
        $date = new DateTime("2026-03-15");
        $this->assertSame("2026-03-10", Helper::get_offset($date, -5));
    }

    public function test_get_offset_crosses_month_boundary(): void
    {
        $date = new DateTime("2026-03-30");
        $this->assertSame("2026-04-02", Helper::get_offset($date, 3));
    }

    public function test_get_offset_crosses_year_boundary(): void
    {
        $date = new DateTime("2026-12-31");
        $this->assertSame("2027-01-01", Helper::get_offset($date, 1));
    }

    public function test_get_offset_crosses_year_boundary_backwards(): void
    {
        $date = new DateTime("2026-01-01");
        $this->assertSame("2025-12-31", Helper::get_offset($date, -1));
    }

    // -----------------------------------------------------------------------
    // dict_to_upsert_parts
    // -----------------------------------------------------------------------

    public function test_upsert_single_column(): void
    {
        [
            $cols,
            $placeholders,
            $updates,
            $types,
            $values,
        ] = Helper::dict_to_upsert_parts(["rsvp" => 1]);

        $this->assertSame("rsvp", $cols);
        $this->assertSame("?", $placeholders);
        $this->assertSame("rsvp = VALUES(rsvp)", $updates);
        $this->assertSame("s", $types);
        $this->assertSame([1], $values);
    }

    public function test_upsert_multiple_columns(): void
    {
        [
            $cols,
            $placeholders,
            $updates,
            $types,
            $values,
        ] = Helper::dict_to_upsert_parts([
            "rsvp" => 1,
            "size" => "MD",
            "adults" => 2,
        ]);

        $this->assertSame("rsvp, size, adults", $cols);
        $this->assertSame("?, ?, ?", $placeholders);
        $this->assertSame(
            "rsvp = VALUES(rsvp), size = VALUES(size), adults = VALUES(adults)",
            $updates,
        );
        $this->assertSame("sss", $types);
        $this->assertSame([1, "MD", 2], $values);
    }

    public function test_upsert_values_are_not_interpolated_into_sql_parts(): void
    {
        // A value containing SQL metacharacters must appear only in $values,
        // never embedded in the cols/placeholders/updates strings.
        $injection = "MD\"; DROP TABLE rsvps; --";
        [
            $cols,
            $placeholders,
            $updates,
            $types,
            $values,
        ] = Helper::dict_to_upsert_parts(["size" => $injection]);

        $this->assertStringNotContainsString($injection, $cols);
        $this->assertStringNotContainsString($injection, $placeholders);
        $this->assertStringNotContainsString($injection, $updates);
        $this->assertSame([$injection], $values);
    }

    public function test_upsert_empty_dict(): void
    {
        [
            $cols,
            $placeholders,
            $updates,
            $types,
            $values,
        ] = Helper::dict_to_upsert_parts([]);

        $this->assertSame("", $cols);
        $this->assertSame("", $placeholders);
        $this->assertSame("", $updates);
        $this->assertSame("", $types);
        $this->assertSame([], $values);
    }

    // -----------------------------------------------------------------------
    // print_to_json
    // -----------------------------------------------------------------------

    private function captureJson(callable $fn): array
    {
        ob_start();
        $fn();
        return json_decode(ob_get_clean(), true);
    }

    public function test_print_to_json_contains_all_keys(): void
    {
        $json = $this->captureJson(
            fn() => Helper::print_to_json(["row1"], "ok", "2026-03-15", [
                "extra",
            ]),
        );

        $this->assertSame("ok", $json["msg"]);
        $this->assertSame("2026-03-15", $json["date"]);
        $this->assertSame(["row1"], $json["data"]);
        $this->assertSame(["extra"], $json["other"]);
    }

    public function test_print_to_json_optional_params_default_to_null(): void
    {
        $json = $this->captureJson(fn() => Helper::print_to_json([], "hello"));

        $this->assertNull($json["date"]);
        $this->assertNull($json["other"]);
    }

    public function test_print_to_json_numeric_check_converts_numeric_strings(): void
    {
        $json = $this->captureJson(
            fn() => Helper::print_to_json([["count" => "5"]], ""),
        );

        // JSON_NUMERIC_CHECK converts numeric strings to numbers
        $this->assertSame(5, $json["data"][0]["count"]);
    }
}
