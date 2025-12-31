<?php

function sizes_downgrade_only($size, $all_sizes)
{
    $size_index = array_search($size, $all_sizes);

    if ($size_index === false) {
        return array_slice($all_sizes, 0, min(2, count($all_sizes)));
    }

    return array_slice($all_sizes, 0, $size_index + 1);
}

function sizes_plus_minus_one($size, $all_sizes)
{
    $size_index = array_search($size, $all_sizes);

    if ($size_index === false) {
        return array_slice($all_sizes, 0, min(2, count($all_sizes)));
    }

    $start_index = max(0, $size_index - 1);
    $end_index = min(count($all_sizes) - 1, $size_index + 1);
    $length = $end_index - $start_index + 1;

    return array_slice($all_sizes, $start_index, $length);
}

function sizes_plus_one($size, $all_sizes)
{
    $size_index = array_search($size, $all_sizes);

    if ($size_index === false) {
        return $all_sizes;
    }

    $start_index = 0;
    $end_index = min(count($all_sizes) - 1, $size_index + 1);
    $length = $end_index - $start_index + 1;

    return array_slice($all_sizes, $start_index, $length);
}

function sizes_any($size, $all_sizes)
{
    return $all_sizes;
}
