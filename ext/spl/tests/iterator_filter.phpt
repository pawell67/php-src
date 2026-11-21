--TEST--
iterator_filter(): lazy, preserves keys, supports ARRAY_FILTER_USE_* modes
--FILE--
<?php

$data = ['a' => 1, 'b' => 0, 'c' => 2, 'd' => null, 'e' => 3];

echo "-- no callback (truthy values)\n";
var_dump(iterator_to_array(iterator_filter($data)));

echo "-- ARRAY_FILTER_USE_VALUE (default)\n";
var_dump(iterator_to_array(iterator_filter($data, fn ($v) => $v > 1)));

echo "-- ARRAY_FILTER_USE_KEY\n";
var_dump(iterator_to_array(iterator_filter($data, fn ($k) => $k === 'b' || $k === 'e', ARRAY_FILTER_USE_KEY)));

echo "-- ARRAY_FILTER_USE_BOTH\n";
iterator_to_array(iterator_filter(['x' => 1], function (...$args) {
    var_dump($args);
    return true;
}, ARRAY_FILTER_USE_BOTH));

echo "-- laziness\n";
$naturals = (function () {
    for ($i = 1; ; $i++) {
        echo "pull $i\n";
        yield $i;
    }
})();
$evens = iterator_filter($naturals, fn (int $x) => $x % 2 === 0);
echo "created\n";
foreach ($evens as $v) {
    echo "got $v\n";
    if ($v >= 4) {
        break;
    }
}

echo "-- invalid mode is rejected at call time\n";
try {
    iterator_filter([1], fn ($v) => true, 42);
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}
?>
--EXPECT--
-- no callback (truthy values)
array(3) {
  ["a"]=>
  int(1)
  ["c"]=>
  int(2)
  ["e"]=>
  int(3)
}
-- ARRAY_FILTER_USE_VALUE (default)
array(2) {
  ["c"]=>
  int(2)
  ["e"]=>
  int(3)
}
-- ARRAY_FILTER_USE_KEY
array(2) {
  ["b"]=>
  int(0)
  ["e"]=>
  int(3)
}
-- ARRAY_FILTER_USE_BOTH
array(2) {
  [0]=>
  int(1)
  [1]=>
  string(1) "x"
}
-- laziness
created
pull 1
pull 2
got 2
pull 3
pull 4
got 4
-- invalid mode is rejected at call time
iterator_filter(): Argument #3 ($mode) must be one of ARRAY_FILTER_USE_VALUE, ARRAY_FILTER_USE_KEY, or ARRAY_FILTER_USE_BOTH
