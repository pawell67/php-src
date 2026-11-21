--TEST--
On arrays, results match the array_* counterparts
--FILE--
<?php

$data = ['a' => 1, 'b' => 0, 'c' => 2, 'd' => 3, 'e' => null];
$even = fn ($v) => $v % 2 === 0;
$byKey = fn ($k) => $k !== 'c';
$both = fn ($v, $k) => $k !== 'a' && $v;

$checks = [
    'map' => [iterator_to_array(iterator_map($data, $even)), array_map($even, $data)],
    'filter' => [iterator_to_array(iterator_filter($data)), array_filter($data)],
    'filter value' => [iterator_to_array(iterator_filter($data, $even)), array_filter($data, $even)],
    'filter key' => [iterator_to_array(iterator_filter($data, $byKey, ARRAY_FILTER_USE_KEY)), array_filter($data, $byKey, ARRAY_FILTER_USE_KEY)],
    'filter both' => [iterator_to_array(iterator_filter($data, $both, ARRAY_FILTER_USE_BOTH)), array_filter($data, $both, ARRAY_FILTER_USE_BOTH)],
    'slice' => [iterator_to_array(iterator_slice($data, 1, 3)), array_slice($data, 1, 3)],
    'keys' => [iterator_to_array(iterator_keys($data)), array_keys($data)],
    'values' => [iterator_to_array(iterator_values($data)), array_values($data)],
    'reduce' => [iterator_reduce($data, fn ($c, $v) => $c + $v, 0), array_reduce($data, fn ($c, $v) => $c + $v, 0)],
    'any' => [iterator_any($data, $both), array_any($data, $both)],
    'all' => [iterator_all($data, $both), array_all($data, $both)],
    'find' => [iterator_find($data, $both), array_find($data, $both)],
    'find_key' => [iterator_find_key($data, $both), array_find_key($data, $both)],
];

foreach ($checks as $name => [$iterator, $array]) {
    echo $name, ': ', $iterator === $array ? 'same' : 'DIFFERENT', "\n";
}
?>
--EXPECT--
map: same
filter: same
filter value: same
filter key: same
filter both: same
slice: same
keys: same
values: same
reduce: same
any: same
all: same
find: same
find_key: same
