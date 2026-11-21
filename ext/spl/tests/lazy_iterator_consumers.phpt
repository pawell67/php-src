--TEST--
LazyIterator: works with the existing consumers of iterators, signatures
--FILE--
<?php

$it = iterator_map(['a' => 1, 'b' => 2], fn ($v) => $v * 10);
var_dump($it instanceof Traversable, $it instanceof Iterator);
var_dump(iterator_count($it));

$it = iterator_values([1, 2, 3]);
var_dump(iterator_apply($it, function () use ($it) {
    echo "apply ", $it->current(), "\n";
    return true;
}, []));

function delegate() {
    yield 'before';
    $result = yield from iterator_slice(['x' => 1, 'y' => 2, 'z' => 3], 1);
    var_dump($result);
    yield 'after';
}
foreach (delegate() as $k => $v) {
    echo json_encode($k), " => ", json_encode($v), "\n";
}

echo "-- dumping does not start the iteration\n";
$it = iterator_map((function () { echo "started\n"; yield 1; })(), fn ($v) => $v);
var_dump($it);
var_dump(json_encode($it));
var_dump((array) $it);
var_dump($it == $it, $it === iterator_map([], fn () => 0));

echo "-- named arguments\n";
var_dump(iterator_to_array(iterator_slice(length: 1, offset: 1, iterator: ['a', 'b', 'c'])));
var_dump(iterator_to_array(iterator_filter(['a' => 0, 'b' => 1], mode: ARRAY_FILTER_USE_KEY, callback: fn ($k) => $k === 'a')));
var_dump(iterator_to_array(iterator_filter([0, 1, 2], mode: ARRAY_FILTER_USE_VALUE)));

echo "-- signatures\n";
foreach (['iterator_map', 'iterator_filter', 'iterator_slice', 'iterator_keys', 'iterator_values'] as $fn) {
    echo (new ReflectionFunction($fn))->__toString();
}

?>
--EXPECTF--
bool(true)
bool(true)
int(2)
apply 1
apply 2
apply 3
int(3)
0 => "before"
"y" => 2
"z" => 3
NULL
1 => "after"
-- dumping does not start the iteration
object(LazyIterator)#%d (0) {
}
string(2) "{}"
array(0) {
}
bool(true)
bool(false)
-- named arguments
array(1) {
  [1]=>
  string(1) "b"
}
array(1) {
  ["a"]=>
  int(0)
}
array(2) {
  [1]=>
  int(1)
  [2]=>
  int(2)
}
-- signatures
Function [ <internal:SPL> function iterator_map ] {

  - Parameters [2] {
    Parameter #0 [ <required> Traversable|array $iterator ]
    Parameter #1 [ <required> callable $callback ]
  }
  - Return [ Iterator ]
}
Function [ <internal:SPL> function iterator_filter ] {

  - Parameters [3] {
    Parameter #0 [ <required> Traversable|array $iterator ]
    Parameter #1 [ <optional> ?callable $callback = null ]
    Parameter #2 [ <optional> int $mode = ARRAY_FILTER_USE_VALUE ]
  }
  - Return [ Iterator ]
}
Function [ <internal:SPL> function iterator_slice ] {

  - Parameters [3] {
    Parameter #0 [ <required> Traversable|array $iterator ]
    Parameter #1 [ <required> int $offset ]
    Parameter #2 [ <optional> ?int $length = null ]
  }
  - Return [ Iterator ]
}
Function [ <internal:SPL> function iterator_keys ] {

  - Parameters [1] {
    Parameter #0 [ <required> Traversable|array $iterator ]
  }
  - Return [ Iterator ]
}
Function [ <internal:SPL> function iterator_values ] {

  - Parameters [1] {
    Parameter #0 [ <required> Traversable|array $iterator ]
  }
  - Return [ Iterator ]
}
