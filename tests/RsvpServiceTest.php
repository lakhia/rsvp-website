<?php

use PHPUnit\Framework\TestCase;

class RsvpServiceTest extends TestCase
{
    // -----------------------------------------------------------------------
    // getDefaultSize
    // -----------------------------------------------------------------------

    public function test_get_default_size_returns_value_from_db(): void
    {
        $result = new class {
            public int $num_rows = 1;
            public function fetch_assoc(): array
            {
                return ["size" => "LG"];
            }
        };
        $db = $this->createMock(DB::class);
        $db->method("query")->willReturn($result);

        $this->assertSame("LG", (new RsvpService($db))->getDefaultSize(42));
    }

    public function test_get_default_size_falls_back_to_md_when_not_found(): void
    {
        $result = new class {
            public int $num_rows = 0;
        };
        $db = $this->createMock(DB::class);
        $db->method("query")->willReturn($result);

        $this->assertSame("MD", (new RsvpService($db))->getDefaultSize(42));
    }

    public function test_get_default_size_falls_back_to_md_on_db_error(): void
    {
        $db = $this->createMock(DB::class);
        $db->method("query")->willReturn(false);

        $this->assertSame("MD", (new RsvpService($db))->getDefaultSize(42));
    }

    // -----------------------------------------------------------------------
    // getEligibleSizes
    // -----------------------------------------------------------------------

    public function test_get_eligible_sizes_admin_gets_all_sizes(): void
    {
        $db = $this->createMock(DB::class);
        $service = new RsvpService($db);

        $this->assertSame(
            Config::THAALI_SIZES,
            $service->getEligibleSizes(true, "XS"),
        );
    }

    public function test_get_eligible_sizes_non_admin_filtered_by_mode(): void
    {
        // Config::SIZE_SELECTION_MODE = "plus_one", so SM -> [XS, SM, MD]
        $db = $this->createMock(DB::class);
        $service = new RsvpService($db);

        $this->assertSame(
            ["XS", "SM", "MD"],
            $service->getEligibleSizes(false, "SM"),
        );
    }

    public function test_get_eligible_sizes_non_admin_largest_returns_all(): void
    {
        $db = $this->createMock(DB::class);
        $service = new RsvpService($db);

        $this->assertSame(
            Config::THAALI_SIZES,
            $service->getEligibleSizes(false, "XL"),
        );
    }

    // -----------------------------------------------------------------------
    // normalizeRow
    // -----------------------------------------------------------------------

    private function baseRow(): array
    {
        return [
            "date" => "2026-03-20",
            "niyaz" => 0,
            "enabled" => 1,
            "rsvp" => 1,
            "adults" => 2,
            "kids" => 1,
            "size" => "MD",
            "lessRice" => 0,
        ];
    }

    public function test_normalize_row_sets_readonly_before_cutoff(): void
    {
        $db = $this->createMock(DB::class);
        $row = $this->baseRow();
        $row["date"] = "2026-03-10";

        $result = (new RsvpService($db))->normalizeRow($row, "2026-03-15", "MD");

        $this->assertSame("1", $result["readonly"]);
    }

    public function test_normalize_row_no_readonly_on_or_after_cutoff(): void
    {
        $db = $this->createMock(DB::class);
        $row = $this->baseRow();
        $row["date"] = "2026-03-20";

        $result = (new RsvpService($db))->normalizeRow($row, "2026-03-15", "MD");

        $this->assertArrayNotHasKey("readonly", $result);
    }

    public function test_normalize_row_removes_falsy_niyaz(): void
    {
        $db = $this->createMock(DB::class);
        $result = (new RsvpService($db))->normalizeRow(
            $this->baseRow(),
            "2026-03-15",
            "MD",
        );

        $this->assertArrayNotHasKey("niyaz", $result);
    }

    public function test_normalize_row_keeps_truthy_niyaz(): void
    {
        $db = $this->createMock(DB::class);
        $row = $this->baseRow();
        $row["niyaz"] = 1;

        $result = (new RsvpService($db))->normalizeRow($row, "2026-03-15", "MD");

        $this->assertSame(1, $result["niyaz"]);
    }

    public function test_normalize_row_removes_adults_kids_when_no_rsvp(): void
    {
        $db = $this->createMock(DB::class);
        $row = $this->baseRow();
        $row["rsvp"] = 0;
        $row["adults"] = 0;
        $row["kids"] = 0;

        $result = (new RsvpService($db))->normalizeRow($row, "2026-03-15", "MD");

        $this->assertArrayNotHasKey("rsvp", $result);
        $this->assertArrayNotHasKey("adults", $result);
        $this->assertArrayNotHasKey("kids", $result);
    }

    public function test_normalize_row_fills_in_default_size_when_absent(): void
    {
        $db = $this->createMock(DB::class);
        $row = $this->baseRow();
        $row["size"] = null;

        $result = (new RsvpService($db))->normalizeRow($row, "2026-03-15", "LG");

        $this->assertSame("LG", $result["size"]);
    }

    public function test_normalize_row_removes_falsy_less_rice(): void
    {
        $db = $this->createMock(DB::class);
        $result = (new RsvpService($db))->normalizeRow(
            $this->baseRow(),
            "2026-03-15",
            "MD",
        );

        $this->assertArrayNotHasKey("lessRice", $result);
    }

    // -----------------------------------------------------------------------
    // validateEntry
    // -----------------------------------------------------------------------

    private function baseEntry(): array
    {
        return ["rsvp" => true, "size" => "MD"];
    }

    public function test_validate_entry_returns_null_before_cutoff(): void
    {
        $db = $this->createMock(DB::class);
        $result = (new RsvpService($db))->validateEntry(
            "2026-03-10",
            "2026-03-15",
            $this->baseEntry(),
            ["XS", "SM", "MD"],
            "MD",
        );

        $this->assertNull($result);
    }

    public function test_validate_entry_allows_date_on_cutoff(): void
    {
        $db = $this->createMock(DB::class);
        $result = (new RsvpService($db))->validateEntry(
            "2026-03-15",
            "2026-03-15",
            $this->baseEntry(),
            ["XS", "SM", "MD"],
            "MD",
        );

        $this->assertNotNull($result);
    }

    public function test_validate_entry_clamps_negative_adults_and_kids(): void
    {
        $db = $this->createMock(DB::class);
        $entry = ["adults" => -3, "kids" => -1, "rsvp" => true, "size" => "MD"];

        $result = (new RsvpService($db))->validateEntry(
            "2026-03-20",
            "2026-03-15",
            $entry,
            ["XS", "SM", "MD"],
            "MD",
        );

        $this->assertSame(0, $result["adults"]);
        $this->assertSame(0, $result["kids"]);
    }

    public function test_validate_entry_sets_rsvp_false_when_adults_and_kids_zero(): void
    {
        $db = $this->createMock(DB::class);
        $entry = ["adults" => 0, "kids" => 0, "rsvp" => true, "size" => "MD"];

        $result = (new RsvpService($db))->validateEntry(
            "2026-03-20",
            "2026-03-15",
            $entry,
            ["XS", "SM", "MD"],
            "MD",
        );

        $this->assertSame(0, $result["rsvp"]);
    }

    public function test_validate_entry_uses_default_size_when_not_set(): void
    {
        $db = $this->createMock(DB::class);
        $entry = ["rsvp" => true];

        $result = (new RsvpService($db))->validateEntry(
            "2026-03-20",
            "2026-03-15",
            $entry,
            ["XS", "SM", "MD"],
            "SM",
        );

        $this->assertSame("SM", $result["size"]);
    }

    public function test_validate_entry_accepts_eligible_size(): void
    {
        $db = $this->createMock(DB::class);
        $entry = ["rsvp" => true, "size" => "SM"];

        $result = (new RsvpService($db))->validateEntry(
            "2026-03-20",
            "2026-03-15",
            $entry,
            ["XS", "SM", "MD"],
            "XS",
        );

        $this->assertSame("SM", $result["size"]);
    }

    public function test_validate_entry_throws_for_ineligible_size(): void
    {
        $db = $this->createMock(DB::class);
        $entry = ["rsvp" => true, "size" => "XL"];

        $this->expectException(\InvalidArgumentException::class);
        (new RsvpService($db))->validateEntry(
            "2026-03-20",
            "2026-03-15",
            $entry,
            ["XS", "SM", "MD"],
            "XS",
        );
    }
}
