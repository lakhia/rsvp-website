<?php

use PHPUnit\Framework\TestCase;

class EstimationTest extends TestCase
{
    // --- parse_menu_name ---

    public function test_parse_menu_name_no_colon(): void
    {
        $this->assertSame("Daal", EstimationService::parse_menu_name("Daal"));
    }

    public function test_parse_menu_name_strips_prefix(): void
    {
        $this->assertSame(
            "Daal",
            EstimationService::parse_menu_name("Niyaz:Daal"),
        );
    }

    public function test_parse_menu_name_trims_whitespace(): void
    {
        $this->assertSame(
            "Daal",
            EstimationService::parse_menu_name("  Daal  "),
        );
    }

    public function test_parse_menu_name_trims_after_colon(): void
    {
        $this->assertSame(
            "Daal",
            EstimationService::parse_menu_name("Niyaz: Daal "),
        );
    }

    public function test_parse_menu_name_colon_at_start(): void
    {
        $this->assertSame("Daal", EstimationService::parse_menu_name(":Daal"));
    }

    // --- get_factor_from_size ---

    public function test_factor_xs(): void
    {
        $this->assertSame(
            0.25,
            EstimationService::get_factor_from_size("XS", 1.0),
        );
    }

    public function test_factor_sm(): void
    {
        $this->assertSame(
            0.5,
            EstimationService::get_factor_from_size("SM", 1.0),
        );
    }

    public function test_factor_md(): void
    {
        $this->assertSame(
            1.0,
            EstimationService::get_factor_from_size("MD", 1.0),
        );
    }

    public function test_factor_lg(): void
    {
        $this->assertSame(
            1.5,
            EstimationService::get_factor_from_size("LG", 1.0),
        );
    }

    public function test_factor_xl(): void
    {
        $this->assertSame(
            2.0,
            EstimationService::get_factor_from_size("XL", 1.0),
        );
    }

    public function test_factor_unknown_size_defaults_to_md(): void
    {
        $this->assertSame(
            1.0,
            EstimationService::get_factor_from_size("XX", 1.0),
        );
    }

    public function test_factor_scales_by_multiplier(): void
    {
        $this->assertSame(
            3.0,
            EstimationService::get_factor_from_size("LG", 2.0),
        );
    }

    public function test_factor_rounds_to_two_decimals(): void
    {
        $this->assertSame(
            0.38,
            EstimationService::get_factor_from_size("XS", 1.5),
        );
    }

    // --- adjust_for_count ---

    public function test_adjust_for_count_non_rice(): void
    {
        $ingredient = [
            "name" => "Dal",
            "unit" => "lbs",
            "multiplier" => 2.0,
            "rice" => 0,
        ];
        $count = ["normalized" => 10.0, "rice+bread" => 8.0];
        $total = [];

        $result = EstimationService::adjust_for_count(
            $ingredient,
            $count,
            $total,
        );

        $this->assertSame("20 lbs Dal", $result);
        $this->assertSame(["lbs Dal" => 20.0], $total);
    }

    public function test_adjust_for_count_rice(): void
    {
        $ingredient = [
            "name" => "Rice",
            "unit" => "lbs",
            "multiplier" => 1.5,
            "rice" => 1,
        ];
        $count = ["normalized" => 10.0, "rice+bread" => 8.0];
        $total = [];

        $result = EstimationService::adjust_for_count(
            $ingredient,
            $count,
            $total,
        );

        $this->assertSame("12 lbs Rice", $result);
        $this->assertSame(["lbs Rice" => 12.0], $total);
    }

    public function test_adjust_for_count_accumulates_total(): void
    {
        $ingredient = [
            "name" => "Dal",
            "unit" => "lbs",
            "multiplier" => 1.0,
            "rice" => 0,
        ];
        $count = ["normalized" => 5.0];
        $total = ["lbs Dal" => 10.0];

        EstimationService::adjust_for_count($ingredient, $count, $total);

        $this->assertSame(15.0, $total["lbs Dal"]);
    }

    public function test_adjust_for_count_missing_count_key_defaults_to_zero(): void
    {
        $ingredient = [
            "name" => "Dal",
            "unit" => "lbs",
            "multiplier" => 2.0,
            "rice" => 0,
        ];
        $count = [];
        $total = [];

        $result = EstimationService::adjust_for_count(
            $ingredient,
            $count,
            $total,
        );

        $this->assertSame("0 lbs Dal", $result);
        $this->assertSame(["lbs Dal" => 0.0], $total);
    }

    public function test_adjust_for_count_rounds_to_two_decimals(): void
    {
        $ingredient = [
            "name" => "Spice",
            "unit" => "tsp",
            "multiplier" => 0.333,
            "rice" => 0,
        ];
        $count = ["normalized" => 10.0];
        $total = [];

        $result = EstimationService::adjust_for_count(
            $ingredient,
            $count,
            $total,
        );

        $this->assertSame("3.33 tsp Spice", $result);
    }
}
