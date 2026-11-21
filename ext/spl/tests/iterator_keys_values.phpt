--TEST--
iterator_keys() / iterator_values(): lazy, produce lists
--FILE--
<?php

// A generator with duplicate keys: iterator_to_array($gen) would lose values.
$gen = function () {
    yield 'k' => 1;
    yield 'k' => 2;
    yield 'm' => 3;
};

var_dump(iterator_to_array(iterator_keys($gen())));
var_dump(iterator_to_array(iterator_values($gen())));

echo "-- laziness\n";
$naturals = (function () {
    for ($i = 1; ; $i++) {
        echo "pull $i\n";
        yield "n$i" => $i;
    }
})();
foreach (iterator_keys($naturals) as $key) {
    echo "key $key\n";
    if ($key === 'n2') {
        break;
    }
}
?>
--EXPECT--
array(3) {
  [0]=>
  string(1) "k"
  [1]=>
  string(1) "k"
  [2]=>
  string(1) "m"
}
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
-- laziness
pull 1
key n1
pull 2
key n2
