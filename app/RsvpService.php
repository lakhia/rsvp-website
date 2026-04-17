<?php

require_once "config.php";
require_once "sizes.php";

class RsvpService
{
    public function __construct(private $db) {}

    /**
     * Returns the user's default thaali size from the database.
     * Falls back to "MD" if the thaali is not found or the query fails.
     */
    public function getDefaultSize(int|string $thaali): string
    {
        $result = $this->db->query("SELECT size FROM family WHERE thaali = " . (int)$thaali);
        if (!$result || $result->num_rows != 1) {
            return "MD";
        }
        $row = $result->fetch_assoc();
        return $row['size'];
    }

    /**
     * Returns the list of thaali sizes the user may select.
     * Admins always get all sizes. Non-admins are filtered by the configured mode.
     *
     * @throws RuntimeException if SIZE_SELECTION_MODE names a non-existent function
     */
    public function getEligibleSizes(bool $isAdmin, string $defaultSize): array
    {
        $allSizes = Config::THAALI_SIZES;

        if ($isAdmin) {
            return $allSizes;
        }

        $fn = "sizes_" . Config::SIZE_SELECTION_MODE;
        if (function_exists($fn)) {
            return $fn($defaultSize, $allSizes);
        }

        throw new \RuntimeException(
            "Thaali size selection not configured correctly: " . Config::SIZE_SELECTION_MODE
        );
    }

    /**
     * Normalises a raw DB row for the RSVP view:
     * - Sets readonly if the date is before the cutoff
     * - Removes falsy fields (niyaz, enabled, lessRice)
     * - Removes adults/kids when there is no RSVP
     * - Fills in defaultSize when size is absent
     */
    public function normalizeRow(array $row, string $cutoff, string $defaultSize): array
    {
        if ($row["date"] < $cutoff) {
            $row["readonly"] = "1";
        }
        if (!$row["niyaz"]) {
            unset($row['niyaz']);
        }
        if (!$row["enabled"]) {
            unset($row["enabled"]);
        }
        if (!$row["rsvp"]) {
            unset($row['rsvp']);
            if ($row['adults'] == 0) {
                unset($row['adults']);
            }
            if ($row['kids'] == 0) {
                unset($row['kids']);
            }
        }
        if (!$row['size']) {
            $row['size'] = $defaultSize;
        }
        if (!$row["lessRice"]) {
            unset($row['lessRice']);
        }
        return $row;
    }

    /**
     * Validates and normalises a single RSVP entry from a POST payload.
     *
     * Returns null if the date is before the cutoff (entry should be skipped).
     * Returns the normalised entry array otherwise.
     *
     * @throws \InvalidArgumentException if the chosen size is not in $eligibleSizes
     */
    public function validateEntry(
        string $date,
        string $cutoff,
        array $entry,
        array $eligibleSizes,
        string $defaultSize
    ): ?array {
        if ($date < $cutoff) {
            return null;
        }

        if (isset($entry['adults'])) {
            if ($entry['adults'] < 0) {
                $entry['adults'] = 0;
            }
            if ($entry['kids'] < 0) {
                $entry['kids'] = 0;
            }
            if ($entry['adults'] + $entry['kids'] == 0) {
                $entry['rsvp'] = 0;
            }
        }

        if (!isset($entry['size'])) {
            $entry['size'] = $defaultSize;
        } elseif (!in_array($entry['size'], $eligibleSizes)) {
            throw new \InvalidArgumentException(
                "You picked a size too large for your family, please try again!"
            );
        }

        return $entry;
    }
}
