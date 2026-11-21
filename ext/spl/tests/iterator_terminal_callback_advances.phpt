--TEST--
iterator_reduce/any/find/first(): callback advancing or releasing the underlying iterator
--FILE--
<?php

function gen() {
    yield 'a' => str_repeat('x', 3);
    yield 'b' => str_repeat('y', 3);
    yield 'c' => str_repeat('z', 3);
}

// The current value must stay valid while the callback runs, even if the
// callback moves the generator forward. Entries are then skipped exactly as
// they would be by foreach.
$g = gen();
var_dump(iterator_find($g, function ($v, $k) use ($g) {
    $g->next();
    echo "callback got $k => $v\n";
    return $k === 'a';
}));

$g = gen();
var_dump(iterator_reduce($g, function ($carry, $v) use ($g) {
    $g->next();
    return $carry . $v;
}, ''));

echo "-- the only reference to the iterator is the argument\n";
var_dump(iterator_any(gen(), fn ($v) => $v === 'zzz'));
var_dump(iterator_first(new ArrayIterator([str_repeat('w', 3)])));

?>
--EXPECT--
callback got a => xxx
string(3) "xxx"
string(6) "xxxzzz"
-- the only reference to the iterator is the argument
bool(true)
string(3) "www"
