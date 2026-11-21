--TEST--
iterator_slice(): pulls exactly what is needed, like the userland polyfill
--FILE--
<?php

// The userland polyfill of iterator_slice() (argument validation omitted).
function polyfill_slice(iterable $iterator, int $offset, ?int $length = null): Generator {
    if ($length === 0) {
        return;
    }
    $skipped = 0;
    $taken = 0;
    foreach ($iterator as $key => $value) {
        if ($skipped < $offset) {
            ++$skipped;
            continue;
        }
        yield $key => $value;
        if ($length !== null && ++$taken >= $length) {
            return;
        }
    }
}

// Logs every call the slice makes on its source.
class LoggingIterator implements Iterator {
    private int $i = 0;
    public function __construct(private array $keys) {}
    public function rewind(): void { echo "  rewind\n"; $this->i = 0; }
    public function valid(): bool { echo "  valid\n"; return $this->i < count($this->keys); }
    public function current(): mixed { echo "  current\n"; return "v{$this->i}"; }
    public function key(): mixed { echo "  key\n"; return $this->keys[$this->i]; }
    public function next(): void { echo "  next\n"; $this->i++; }
    public function __destruct() { echo "  [source destroyed]\n"; }
}

class LoggingAggregate implements IteratorAggregate {
    public function getIterator(): Iterator {
        echo "  getIterator\n";
        return new ArrayIterator([1, 2]);
    }
}

$scenarios = [
    'offset and length' => [[5, 5, 'x', 7], 1, 2],
    'duplicate and integer keys are preserved, not renumbered' => [[5, 5, 'x', 7], 0, null],
    'offset past the end' => [['a', 'b'], 5, null],
    'length past the end' => [['a', 'b'], 1, 10],
    'length 0 does not touch the source' => [['a', 'b'], 1, 0],
];

foreach ($scenarios as $name => [$keys, $offset, $length]) {
    echo "-- $name\n";
    $results = [];
    foreach (['polyfill' => polyfill_slice(...), 'native' => iterator_slice(...)] as $impl => $slice) {
        ob_start();
        $it = $slice(new LoggingIterator($keys), $offset, $length);
        echo "  created\n";
        foreach ($it as $k => $v) {
            echo "  => ", json_encode($k), ": $v\n";
        }
        echo "  loop done\n";
        unset($it);
        $results[$impl] = ob_get_clean();
    }
    echo $results['native'];
    echo $results['polyfill'] === $results['native'] ? "  => same as polyfill\n" : "  => DIFFERENT, polyfill:\n" . $results['polyfill'];
}

echo "-- length 0 does not call getIterator()\n";
foreach (iterator_slice(new LoggingAggregate(), 0, 0) as $_) {
}
foreach (iterator_slice(new LoggingAggregate(), 0, 1) as $v) {
    echo "  => $v\n";
}

echo "-- an infinite source\n";
$naturals = (function () {
    for ($i = 1; ; $i++) {
        yield $i;
    }
})();
var_dump(iterator_to_array(iterator_slice($naturals, 2, 3)));

echo "-- arguments are validated at call time\n";
foreach ([[-1, null], [0, -1]] as [$offset, $length]) {
    try {
        iterator_slice(new LoggingAggregate(), $offset, $length);
    } catch (ValueError $e) {
        echo $e->getMessage(), "\n";
    }
}

?>
--EXPECT--
-- offset and length
  created
  rewind
  valid
  current
  key
  next
  valid
  current
  key
  => 5: v1
  next
  valid
  current
  key
  => "x": v2
  [source destroyed]
  loop done
  => same as polyfill
-- duplicate and integer keys are preserved, not renumbered
  created
  rewind
  valid
  current
  key
  => 5: v0
  next
  valid
  current
  key
  => 5: v1
  next
  valid
  current
  key
  => "x": v2
  next
  valid
  current
  key
  => 7: v3
  next
  valid
  [source destroyed]
  loop done
  => same as polyfill
-- offset past the end
  created
  rewind
  valid
  current
  key
  next
  valid
  current
  key
  next
  valid
  [source destroyed]
  loop done
  => same as polyfill
-- length past the end
  created
  rewind
  valid
  current
  key
  next
  valid
  current
  key
  => "b": v1
  next
  valid
  [source destroyed]
  loop done
  => same as polyfill
-- length 0 does not touch the source
  created
  [source destroyed]
  loop done
  => same as polyfill
-- length 0 does not call getIterator()
  getIterator
  => 1
-- an infinite source
array(3) {
  [2]=>
  int(3)
  [3]=>
  int(4)
  [4]=>
  int(5)
}
-- arguments are validated at call time
iterator_slice(): Argument #2 ($offset) must be greater than or equal to 0
iterator_slice(): Argument #3 ($length) must be greater than or equal to 0
