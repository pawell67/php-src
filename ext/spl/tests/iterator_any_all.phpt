--TEST--
iterator_any() / iterator_all(): short-circuit, callback receives ($value, $key)
--FILE--
<?php

function naturals() {
    for ($i = 1; ; $i++) {
        echo "pull $i\n";
        yield "n$i" => $i;
    }
}

var_dump(iterator_any(naturals(), fn ($v) => $v === 3));
var_dump(iterator_all(naturals(), fn ($v) => $v < 2));

echo "-- key is passed as the second argument\n";
var_dump(iterator_any(['a' => 1, 'b' => 2], fn ($v, $k) => $k === 'b'));
var_dump(iterator_all(new ArrayIterator(['a' => 1, 'b' => 2]), fn ($v, $k) => is_string($k)));

echo "-- truthiness of the callback result\n";
var_dump(iterator_any([1, 2], fn ($v) => $v === 2 ? 'yes' : 0));
var_dump(iterator_all([1, 2], fn ($v) => $v === 2 ? [] : 1));

echo "-- empty input\n";
var_dump(iterator_any([], fn () => true));
var_dump(iterator_all([], fn () => false));
var_dump(iterator_any(new EmptyIterator(), fn () => true));
var_dump(iterator_all(new EmptyIterator(), fn () => false));

?>
--EXPECT--
pull 1
pull 2
pull 3
bool(true)
pull 1
pull 2
bool(false)
-- key is passed as the second argument
bool(true)
bool(true)
-- truthiness of the callback result
bool(true)
bool(false)
-- empty input
bool(false)
bool(true)
bool(false)
bool(true)
