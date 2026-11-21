--TEST--
iterator_find() / iterator_find_key(): short-circuit, callback receives ($value, $key)
--FILE--
<?php

function naturals() {
    for ($i = 1; ; $i++) {
        echo "pull $i\n";
        yield "n$i" => $i;
    }
}

var_dump(iterator_find(naturals(), fn ($v) => $v % 2 === 0));
var_dump(iterator_find_key(naturals(), fn ($v) => $v % 2 === 0));

echo "-- key is passed as the second argument\n";
var_dump(iterator_find(['a' => 1, 'b' => 2], fn ($v, $k) => $k === 'b'));
var_dump(iterator_find_key(new ArrayIterator(['a' => 1, 'b' => 2]), fn ($v, $k) => $v === 1));

echo "-- not found\n";
var_dump(iterator_find([1, 2], fn ($v) => $v > 5));
var_dump(iterator_find_key((fn () => yield from [1, 2])(), fn ($v) => $v > 5));

echo "-- iterator keys are not restricted to int|string\n";
function weird_keys() {
    yield 2.5 => 'float';
    yield null => 'null';
    yield [1, 2] => 'array';
    yield new ArrayObject() => 'object';
}
var_dump(iterator_find_key(weird_keys(), fn ($v) => $v === 'float'));
var_dump(iterator_find_key(weird_keys(), fn ($v) => $v === 'null'));
var_dump(iterator_find_key(weird_keys(), fn ($v) => $v === 'array'));
var_dump(iterator_find_key(weird_keys(), fn ($v, $k) => is_object($k)));

echo "-- values are returned dereferenced\n";
$x = 1;
$data = [&$x];
$found = iterator_find($data, fn () => true);
$found++;
var_dump($x);

echo "-- the callback may return any value, which is converted to bool\n";
$truthy = fn ($v) => $v > 1 ? str_repeat('y', 3) : [];
var_dump(iterator_find([1, 2, 3], $truthy), iterator_find_key([1, 2, 3], $truthy));

?>
--EXPECTF--
pull 1
pull 2
int(2)
pull 1
pull 2
string(2) "n2"
-- key is passed as the second argument
int(2)
string(1) "a"
-- not found
NULL
NULL
-- iterator keys are not restricted to int|string
float(2.5)
NULL
array(2) {
  [0]=>
  int(1)
  [1]=>
  int(2)
}
object(ArrayObject)#%d (1) {
  ["storage":"ArrayObject":private]=>
  array(0) {
  }
}
-- values are returned dereferenced
int(1)
-- the callback may return any value, which is converted to bool
int(2)
int(1)
