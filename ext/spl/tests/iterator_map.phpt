--TEST--
iterator_map(): lazy, preserves keys, passes the value only
--FILE--
<?php

$source = (function () {
    foreach (['a' => 1, 'b' => 2, 'c' => 3] as $k => $v) {
        echo "pull $k\n";
        yield $k => $v;
    }
})();

$mapped = iterator_map($source, function (int $x) {
    echo "map $x\n";
    return $x * 10;
});
echo "created\n";

foreach ($mapped as $k => $v) {
    echo "$k => $v\n";
}

echo "--\n";
// Value-only callback: first-class callables of internal functions work,
// with coercive typing like array_map().
var_dump(iterator_to_array(iterator_map(['x' => 'abc', 'y' => 12345], strlen(...))));
?>
--EXPECT--
created
pull a
map 1
a => 10
pull b
map 2
b => 20
pull c
map 3
c => 30
--
array(2) {
  ["x"]=>
  int(3)
  ["y"]=>
  int(5)
}
