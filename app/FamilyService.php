<?php

require_once "config.php";
require_once "Helper.php";

class FamilyService
{
    /**
     * Validates and normalises a single family entry from a POST payload.
     *
     * Returns null if email is empty (signals the caller to delete the record).
     * Returns a normalised array ready for upsert otherwise.
     *
     * @throws \InvalidArgumentException if firstName or lastName is missing
     */
    public function normalizeEntry(object $entry): ?array
    {
        $email = Helper::get_if_defined($entry->email, "");

        if ($email === "") {
            return null;
        }

        $lastName = Helper::get_if_defined($entry->lastName, "");
        $firstName = Helper::get_if_defined($entry->firstName, "");

        if ($lastName === "" || $firstName === "") {
            throw new \InvalidArgumentException("name is required");
        }

        $size = strtoupper(Helper::get_if_defined($entry->size, "MD"));
        if (!in_array($size, Config::THAALI_SIZES)) {
            $size = "MD";
        }

        return [
            'thaali'    => $entry->thaali,
            'its'       => Helper::get_if_defined($entry->its,   ''),
            'lastName'  => $lastName,
            'firstName' => $firstName,
            'size'      => $size,
            'area'      => Helper::get_if_defined($entry->area,  ''),
            'email'     => $email,
            'phone'     => Helper::get_if_defined($entry->phone, ''),
            'poc'       => Helper::get_if_defined($entry->poc,   ''),
            'resp'      => Helper::get_if_defined($entry->resp,  ''),
        ];
    }
}
