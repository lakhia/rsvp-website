$size_index = array_search($size, $all_sizes);

if ($size_index === false) {
    return array_slice($all_sizes, 0, min(2, count($all_sizes)));
}

return array_slice($all_sizes, 0, $size_index + 1);
