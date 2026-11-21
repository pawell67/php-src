--TEST--
iterator_reduce/any/all/find/find_key/first(): same results as the array_* counterparts on arrays
--FILE--
<?php

$data = ['a' => 1, 'b' => 0, 'c' => 2, 'd' => 3, 'e' => null, 5 => 'five'];
$callbacks = [
    'by value' => fn ($v) => is_int($v) && $v % 2 === 1,
    'by key' => fn ($v, $k) => $k === 'c' || $k === 5,
    'never' => fn () => false,
    'always' => fn () => true,
];

foreach ([$data, []] as $input) {
    foreach ($callbacks as $name => $cb) {
        $same = iterator_any($input, $cb) === array_any($input, $cb)
            && iterator_all($input, $cb) === array_all($input, $cb)
            && iterator_find($input, $cb) === array_find($input, $cb)
            && iterator_find_key($input, $cb) === array_find_key($input, $cb)
            && iterator_find(new ArrayIterator($input), $cb) === array_find($input, $cb)
            && iterator_find_key(new ArrayIterator($input), $cb) === array_find_key($input, $cb);
        echo count($input), " entries, $name: ", $same ? 'same' : 'DIFFERENT', "\n";
    }
    $concat = fn ($c, $v) => $c . '|' . var_export($v, true);
    var_dump(iterator_reduce($input, $concat, '') === array_reduce($input, $concat, ''));
    var_dump(iterator_first($input) === array_first($input));
}

?>
--EXPECT--
6 entries, by value: same
6 entries, by key: same
6 entries, never: same
6 entries, always: same
bool(true)
bool(true)
0 entries, by value: same
0 entries, by key: same
0 entries, never: same
0 entries, always: same
bool(true)
bool(true)
