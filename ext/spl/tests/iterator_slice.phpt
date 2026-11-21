--TEST--
iterator_slice(): offset/length, preserves keys, never over-pulls, validates eagerly
--FILE--
<?php

$data = ['a' => 1, 'b' => 2, 5 => 3, 9 => 4];

echo "-- offset 1, length 2 (keys preserved, int keys not renumbered)\n";
var_dump(iterator_to_array(iterator_slice($data, 1, 2)));

echo "-- offset 2, length null (rest)\n";
var_dump(iterator_to_array(iterator_slice($data, 2)));

echo "-- offset past the end\n";
var_dump(iterator_to_array(iterator_slice($data, 10)));

echo "-- length past the end\n";
var_dump(iterator_to_array(iterator_slice([1, 2], 0, 5)));

echo "-- infinite source: pulls exactly offset + length entries\n";
$naturals = (function () {
    for ($i = 1; ; $i++) {
        echo "pull $i\n";
        yield $i;
    }
})();
foreach (iterator_slice($naturals, 2, 2) as $k => $v) {
    echo "$k => $v\n";
}

echo "-- length 0 does not touch the input\n";
$untouched = (function () {
    echo "should not run\n";
    yield 1;
})();
var_dump(iterator_to_array(iterator_slice($untouched, 3, 0)));

echo "-- negative arguments are rejected at call time\n";
foreach ([[-1, null], [0, -1]] as [$offset, $length]) {
    try {
        iterator_slice([1], $offset, $length);
    } catch (ValueError $e) {
        echo $e->getMessage(), "\n";
    }
}
?>
--EXPECT--
-- offset 1, length 2 (keys preserved, int keys not renumbered)
array(2) {
  ["b"]=>
  int(2)
  [5]=>
  int(3)
}
-- offset 2, length null (rest)
array(2) {
  [5]=>
  int(3)
  [9]=>
  int(4)
}
-- offset past the end
array(0) {
}
-- length past the end
array(2) {
  [0]=>
  int(1)
  [1]=>
  int(2)
}
-- infinite source: pulls exactly offset + length entries
pull 1
pull 2
pull 3
2 => 3
pull 4
3 => 4
-- length 0 does not touch the input
array(0) {
}
-- negative arguments are rejected at call time
iterator_slice(): Argument #2 ($offset) must be greater than or equal to 0
iterator_slice(): Argument #3 ($length) must be greater than or equal to 0
