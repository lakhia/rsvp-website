<?php

use PHPUnit\Framework\TestCase;

class FamilyServiceTest extends TestCase
{
    private function baseEntry(array $overrides = []): object
    {
        return (object) array_merge(
            [
                "thaali" => 5,
                "its" => "12345678",
                "firstName" => "Fatema",
                "lastName" => "Hussain",
                "size" => "MD",
                "area" => "South Bay",
                "email" => "fatema@example.com",
                "phone" => "555-1234",
                "poc" => "Y",
                "resp" => "F",
            ],
            $overrides,
        );
    }

    // --- delete signal ---

    public function test_empty_email_returns_null(): void
    {
        $entry = $this->baseEntry(["email" => ""]);
        $this->assertNull((new FamilyService())->normalizeEntry($entry));
    }

    public function test_missing_email_property_returns_null(): void
    {
        $entry = (object) [
            "thaali" => 5,
            "firstName" => "Fatema",
            "lastName" => "Hussain",
        ];
        $this->assertNull((new FamilyService())->normalizeEntry($entry));
    }

    // --- name validation ---

    public function test_missing_last_name_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new FamilyService())->normalizeEntry(
            $this->baseEntry(["lastName" => ""]),
        );
    }

    public function test_missing_first_name_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new FamilyService())->normalizeEntry(
            $this->baseEntry(["firstName" => ""]),
        );
    }

    // --- size normalisation ---

    public function test_size_is_uppercased(): void
    {
        $result = (new FamilyService())->normalizeEntry(
            $this->baseEntry(["size" => "lg"]),
        );
        $this->assertSame("LG", $result["size"]);
    }

    public function test_unknown_size_defaults_to_md(): void
    {
        $result = (new FamilyService())->normalizeEntry(
            $this->baseEntry(["size" => "XXL"]),
        );
        $this->assertSame("MD", $result["size"]);
    }

    public function test_missing_size_defaults_to_md(): void
    {
        $entry = $this->baseEntry();
        unset($entry->size);
        $result = (new FamilyService())->normalizeEntry($entry);
        $this->assertSame("MD", $result["size"]);
    }

    // --- optional field defaults ---

    public function test_missing_optional_fields_default_to_empty_string(): void
    {
        $entry = (object) [
            "thaali" => 7,
            "firstName" => "Ali",
            "lastName" => "Bhaisaheb",
            "email" => "ali@example.com",
        ];
        $result = (new FamilyService())->normalizeEntry($entry);

        $this->assertSame("", $result["its"]);
        $this->assertSame("", $result["area"]);
        $this->assertSame("", $result["phone"]);
        $this->assertSame("", $result["poc"]);
        $this->assertSame("", $result["resp"]);
    }

    // --- happy path ---

    public function test_valid_entry_returns_all_fields(): void
    {
        $result = (new FamilyService())->normalizeEntry($this->baseEntry());

        $this->assertSame(5, $result["thaali"]);
        $this->assertSame("12345678", $result["its"]);
        $this->assertSame("Hussain", $result["lastName"]);
        $this->assertSame("Fatema", $result["firstName"]);
        $this->assertSame("MD", $result["size"]);
        $this->assertSame("South Bay", $result["area"]);
        $this->assertSame("fatema@example.com", $result["email"]);
        $this->assertSame("555-1234", $result["phone"]);
        $this->assertSame("Y", $result["poc"]);
        $this->assertSame("F", $result["resp"]);
    }
}
