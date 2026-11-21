--TEST--
LazyIterator: unusual sources behave like the userland polyfill
--FILE--
<?php

function polyfill_values(iterable $iterator): Generator {
    foreach ($iterator as $value) {
        yield $value;
    }
}
function polyfill_map(iterable $iterator, callable $callback): Generator {
    foreach ($iterator as $key => $value) {
        yield $key => $callback($value);
    }
}

// Throws from the given Iterator method on its n-th call.
class ThrowingIterator implements Iterator {
    private int $i = 0;
    private array $calls = [];
    public function __construct(private string $method, private int $n = 2) {}
    private function track(string $method): void {
        $this->calls[$method] = ($this->calls[$method] ?? 0) + 1;
        if ($method === $this->method && $this->calls[$method] === $this->n) {
            throw new Exception("$method threw");
        }
    }
    public function rewind(): void { $this->track('rewind'); $this->i = 0; }
    public function valid(): bool { $this->track('valid'); return $this->i < 3; }
    // A refcounted value, so that leaking it would be detected.
    public function current(): mixed { $this->track('current'); return "v{$this->i}"; }
    public function key(): mixed { $this->track('key'); return "k{$this->i}"; }
    public function next(): void { $this->track('next'); $this->i++; }
}

class ThrowsOnRelease implements IteratorAggregate {
    public function getIterator(): Generator {
        try {
            yield 1;
            yield 2;
        } finally {
            echo "  [finally]\n";
            throw new Exception('thrown when released');
        }
    }
}

function inner() {
    yield 'i1';
    yield 'i2';
}
function delegating() {
    yield 'd1';
    yield from inner();
    yield 'd2';
}
function &by_ref() {
    $x = 1;
    yield $x;
    $x = 2;
    yield $x;
}

function dump(iterable $it) {
    try {
        foreach ($it as $k => $v) {
            echo "  ", json_encode($k), " => ", json_encode($v), "\n";
        }
        echo "  end\n";
    } catch (Exception $e) {
        echo "  ", get_class($e), ": ", $e->getMessage(), "\n";
    }
}

$scenarios = [
    'a lazy iterator as the source' => fn ($values, $map) => dump($map($values([1, 2]), fn ($v) => $v * 10)),
    'an advanced lazy iterator as the source' => function ($values, $map) {
        $inner = $values([1, 2, 3]);
        $inner->next();
        // The message names the class ("generator" in the polyfill).
        try {
            foreach ($map($inner, fn ($v) => $v * 10) as $_) {
            }
        } catch (Exception $e) {
            echo "  ", get_class($e), "\n";
        }
    },
    'two lazy iterators sharing a generator' => function ($values, $map) {
        $g = (function () { yield 1; yield 2; yield 3; yield 4; })();
        $a = $values($g);
        $b = $values($g);
        foreach (['a' => $a, 'b' => $b, 'a again' => $a, 'b again' => $b] as $name => $it) {
            try {
                $r = json_encode($it->current());
                $it->next();
            } catch (Exception $e) {
                $r = $e->getMessage();
            }
            echo "  $name: $r\n";
        }
    },
    'a closed generator' => function ($values, $map) {
        $g = (function () { yield 1; })();
        foreach ($g as $_) {
        }
        echo "  created: ", get_debug_type($values($g)), "\n";
        dump($values($g));
    },
    'a by-reference generator' => function ($values, $map) {
        $result = iterator_to_array($values(by_ref()));
        $result[0] = 'changed';
        var_dump($result);
    },
    'a delegating generator' => fn ($values, $map) => dump($values(delegating())),
    'the source throws from rewind()' => fn ($values, $map) => dump($values(new ThrowingIterator('rewind', 1))),
    'the source throws from valid()' => fn ($values, $map) => dump($values(new ThrowingIterator('valid'))),
    'the source throws from current()' => fn ($values, $map) => dump($values(new ThrowingIterator('current'))),
    'the source throws from key()' => fn ($values, $map) => dump($map(new ThrowingIterator('key'), fn ($v) => $v)),
    'the source throws from next()' => fn ($values, $map) => dump($values(new ThrowingIterator('next'))),
    'the source throws when it is released at the end' => fn ($values, $map) => dump($values(new ThrowsOnRelease())),
    'an array changed after the iterator was created' => function ($values, $map) {
        $x = 'x';
        $array = [1, &$x];
        $it = $values($array);
        $array[] = 'appended';
        $array[0] = 'overwritten';
        $x = 'changed through the reference';
        dump($it);
    },
];

foreach ($scenarios as $name => $scenario) {
    echo "-- $name\n";
    $results = [];
    foreach (['polyfill' => [polyfill_values(...), polyfill_map(...)], 'native' => [iterator_values(...), iterator_map(...)]] as $impl => [$values, $map]) {
        ob_start();
        $scenario($values, $map);
        $results[$impl] = ob_get_clean();
    }
    // The class of the returned object is the only expected difference.
    $results['polyfill'] = str_replace('Generator', 'LazyIterator', $results['polyfill']);
    echo $results['native'];
    echo $results['polyfill'] === $results['native'] ? "  => same as polyfill\n" : "  => DIFFERENT, polyfill:\n" . $results['polyfill'];
}

?>
--EXPECT--
-- a lazy iterator as the source
  0 => 10
  1 => 20
  end
  => same as polyfill
-- an advanced lazy iterator as the source
  Exception
  => same as polyfill
-- two lazy iterators sharing a generator
  a: 1
  b: Cannot rewind a generator that was already run
  a again: 2
  b again: null
  => same as polyfill
-- a closed generator
  created: LazyIterator
  Exception: Cannot traverse an already closed generator
  => same as polyfill
-- a by-reference generator
array(2) {
  [0]=>
  string(7) "changed"
  [1]=>
  int(2)
}
  => same as polyfill
-- a delegating generator
  0 => "d1"
  1 => "i1"
  2 => "i2"
  3 => "d2"
  end
  => same as polyfill
-- the source throws from rewind()
  Exception: rewind threw
  => same as polyfill
-- the source throws from valid()
  0 => "v0"
  Exception: valid threw
  => same as polyfill
-- the source throws from current()
  0 => "v0"
  Exception: current threw
  => same as polyfill
-- the source throws from key()
  "k0" => "v0"
  Exception: key threw
  => same as polyfill
-- the source throws from next()
  0 => "v0"
  1 => "v1"
  Exception: next threw
  => same as polyfill
-- the source throws when it is released at the end
  0 => 1
  1 => 2
  [finally]
  Exception: thrown when released
  => same as polyfill
-- an array changed after the iterator was created
  0 => 1
  1 => "changed through the reference"
  end
  => same as polyfill
