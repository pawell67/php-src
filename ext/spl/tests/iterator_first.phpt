--TEST--
iterator_first(): pulls at most one entry, null when empty
--FILE--
<?php

function naturals() {
    for ($i = 1; ; $i++) {
        echo "pull $i\n";
        yield "n$i" => $i;
    }
}

var_dump(iterator_first(naturals()));
var_dump(iterator_first([]));
var_dump(iterator_first(['x' => 'a', 'y' => 'b']));
var_dump(iterator_first(new ArrayObject([7, 8])));
var_dump(iterator_first(new EmptyIterator()));

class Agg implements IteratorAggregate {
    public function getIterator(): Iterator {
        yield 'from aggregate';
    }
}
var_dump(iterator_first(new Agg()));

echo "-- like foreach, rewinds first: a generator that was already advanced throws\n";
$g = naturals();
var_dump(iterator_first($g), iterator_first($g));
$g->next();
try {
    iterator_first($g);
} catch (Exception $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}

echo "-- on arrays, same result as array_first()\n";
$x = 1;
$data = [&$x, 2];
var_dump(iterator_first($data) === array_first($data));

?>
--EXPECT--
pull 1
int(1)
NULL
string(1) "a"
int(7)
NULL
string(14) "from aggregate"
-- like foreach, rewinds first: a generator that was already advanced throws
pull 1
int(1)
int(1)
pull 2
Exception: Cannot rewind a generator that was already run
-- on arrays, same result as array_first()
bool(true)
