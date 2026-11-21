--TEST--
LazyIterator: garbage collection and releases during the iteration
--FILE--
<?php

class Noisy {
    public function __construct(public string $name) {}
    public function __destruct() { echo "[{$this->name} destroyed]\n"; }
}

echo "-- gc_collect_cycles() inside the callback\n";
$it = iterator_map([1, 2], function ($v) {
    // A collectable cycle, so that the collector really runs.
    $a = new ArrayObject();
    $a['self'] = $a;
    unset($a);
    var_dump(gc_collect_cycles() > 0);
    return $v;
});
var_dump(iterator_to_array($it));

echo "-- the callback releases the last variable holding the iterator\n";
$it = iterator_map([1, 2, 3], function ($v) {
    unset($GLOBALS['it']);
    return $v;
});
var_dump(iterator_to_array($it));
var_dump(isset($it));

echo "-- the source is released while the iterator is in use\n";
function run() {
    $source = new ArrayIterator([new Noisy('entry'), 2]);
    $it = iterator_values($source);
    unset($source);
    foreach ($it as $v) {
        echo get_debug_type($v), "\n";
    }
    echo "loop done\n";
}
run();

echo "-- map returns the iterator itself\n";
$it = iterator_map([new Noisy('source entry')], function ($v) use (&$it) {
    return $it;
});
$it->current();
unset($it);
var_dump(gc_collect_cycles() > 0);

?>
--EXPECT--
-- gc_collect_cycles() inside the callback
bool(true)
bool(true)
array(2) {
  [0]=>
  int(1)
  [1]=>
  int(2)
}
-- the callback releases the last variable holding the iterator
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
bool(false)
-- the source is released while the iterator is in use
Noisy
int
[entry destroyed]
loop done
-- map returns the iterator itself
[source entry destroyed]
bool(true)
