<?php

use PHPUnit\Framework\TestCase;

class SizesTest extends TestCase
{
    private array $sizes = ["XS", "SM", "MD", "LG", "XL"];

    // --- sizes_downgrade_only ---

    public function test_downgrade_only_mid_size(): void
    {
        $this->assertSame(
            ["XS", "SM", "MD"],
            sizes_downgrade_only("MD", $this->sizes),
        );
    }

    public function test_downgrade_only_smallest(): void
    {
        $this->assertSame(["XS"], sizes_downgrade_only("XS", $this->sizes));
    }

    public function test_downgrade_only_largest(): void
    {
        $this->assertSame(
            ["XS", "SM", "MD", "LG", "XL"],
            sizes_downgrade_only("XL", $this->sizes),
        );
    }

    public function test_downgrade_only_unknown_size_returns_first_two(): void
    {
        $this->assertSame(
            ["XS", "SM"],
            sizes_downgrade_only("XX", $this->sizes),
        );
    }

    public function test_downgrade_only_unknown_size_short_list(): void
    {
        $this->assertSame(["XS"], sizes_downgrade_only("XX", ["XS"]));
    }

    // --- sizes_plus_minus_one ---

    public function test_plus_minus_one_mid_size(): void
    {
        $this->assertSame(
            ["SM", "MD", "LG"],
            sizes_plus_minus_one("MD", $this->sizes),
        );
    }

    public function test_plus_minus_one_smallest_clamps_lower(): void
    {
        $this->assertSame(
            ["XS", "SM"],
            sizes_plus_minus_one("XS", $this->sizes),
        );
    }

    public function test_plus_minus_one_largest_clamps_upper(): void
    {
        $this->assertSame(
            ["LG", "XL"],
            sizes_plus_minus_one("XL", $this->sizes),
        );
    }

    public function test_plus_minus_one_unknown_size_returns_first_two(): void
    {
        $this->assertSame(
            ["XS", "SM"],
            sizes_plus_minus_one("XX", $this->sizes),
        );
    }

    // --- sizes_plus_one ---

    public function test_plus_one_mid_size(): void
    {
        $this->assertSame(
            ["XS", "SM", "MD", "LG"],
            sizes_plus_one("MD", $this->sizes),
        );
    }

    public function test_plus_one_smallest(): void
    {
        $this->assertSame(["XS", "SM"], sizes_plus_one("XS", $this->sizes));
    }

    public function test_plus_one_largest_includes_all(): void
    {
        $this->assertSame(
            ["XS", "SM", "MD", "LG", "XL"],
            sizes_plus_one("XL", $this->sizes),
        );
    }

    public function test_plus_one_unknown_size_returns_all(): void
    {
        $this->assertSame(
            ["XS", "SM", "MD", "LG", "XL"],
            sizes_plus_one("XX", $this->sizes),
        );
    }

    // --- sizes_any ---

    public function test_any_returns_all_sizes(): void
    {
        $this->assertSame($this->sizes, sizes_any("MD", $this->sizes));
    }

    public function test_any_ignores_size_argument(): void
    {
        $this->assertSame($this->sizes, sizes_any("XX", $this->sizes));
    }
}
