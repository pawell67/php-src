--TEST--
iterator_reduce(): same callback arguments and semantics as array_reduce()
--FILE--
<?php

function gen() {
    yield 'a' => 1;
    yield 'b' => 2;
    yield 'c' => 3;
}

var_dump(iterator_reduce(gen(), fn ($carry, $v) => $carry + $v, 10));
var_dump(iterator_reduce([], fn ($carry, $v) => $carry + $v));
var_dump(iterator_reduce(new ArrayIterator(['x', 'y']), fn ($carry, $v) => $carry . $v, '>'));

echo "-- the carry can be an array modified by the callback\n";
var_dump(iterator_reduce(gen(), function (array $carry, int $v) {
    $carry[] = $v * 2;
    return $carry;
}, []));

echo "-- on arrays, same result as array_reduce()\n";
$data = ['a' => 1, 'b' => 2, 'c' => 3];
$cb = fn ($carry, $v) => $carry * 10 + $v;
var_dump(iterator_reduce($data, $cb, 0) === array_reduce($data, $cb, 0));

echo "-- a callback returning by reference yields a plain value\n";
$byRef = function &($carry, $v) {
    $carry .= $v;
    return $carry;
};
$x = iterator_reduce(gen(), $byRef, str_repeat('>', 2));
var_dump($x, $x === array_reduce([1, 2, 3], $byRef, '>>'));

?>
--EXPECT--
int(16)
NULL
string(3) ">xy"
-- the carry can be an array modified by the callback
array(3) {
  [0]=>
  int(2)
  [1]=>
  int(4)
  [2]=>
  int(6)
}
-- on arrays, same result as array_reduce()
bool(true)
-- a callback returning by reference yields a plain value
string(5) ">>123"
bool(true)
