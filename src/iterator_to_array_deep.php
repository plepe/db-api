<?php
// source: https://gist.github.com/jm42/cb328106f393eeb28751#file-groupiteratortest-php
function iterator_to_array_deep(\Traversable $iterator, $use_keys = true) {
    $array = array();
    foreach ($iterator as $key => $value) {
        if ($value instanceof \Iterator) {
            $value = iterator_to_array_deep($value, $use_keys);
        }
        if ($use_keys) {
            $array[$key] = $value;
        } else {
            $array[] = $value;
        }
    }
    return $array;
}
