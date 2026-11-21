--TEST--
LazyIterator: cycles through the source are collected
--FILE--
<?php

class Noisy {
    public function __destruct() { echo "destroyed\n"; }
}

foreach (['not started' => 0, 'started' => 1] as $name => $pulls) {
    echo "-- $name\n";
    // The source holds the iterator over itself.
    $source = new ArrayObject(['noisy' => new Noisy()]);
    $it = iterator_keys($source);
    $source['self'] = $it;
    for ($i = 0; $i < $pulls; $i++) {
        $it->current();
    }
    unset($source, $it);
    echo "unset\n";
    var_dump(gc_collect_cycles() > 0);
}

echo "-- the callback holds the iterator\n";
foreach (['map' => iterator_map(...), 'filter' => iterator_filter(...)] as $name => $fn) {
    $guard = new Noisy();
    $it = $fn([1, 2], function ($v) use (&$it, $guard) { return $v; });
    unset($guard);
    $it->current();
    unset($it);
    echo "$name unset\n";
    var_dump(gc_collect_cycles() > 0);
}

?>
--EXPECT--
-- not started
unset
destroyed
bool(true)
-- started
unset
destroyed
bool(true)
-- the callback holds the iterator
map unset
destroyed
bool(true)
filter unset
destroyed
bool(true)
