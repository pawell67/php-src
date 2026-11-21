--TEST--
LazyIterator: exceptions end the iteration, the source is released when it ends
--FILE--
<?php

function polyfill_values(iterable $iterator): Generator {
    foreach ($iterator as $value) {
        yield $value;
    }
}

class Noisy {
    public function __construct(public string $name) {}
    public function __destruct() { echo "  [{$this->name} destroyed]\n"; }
}

class NoisyIterator extends ArrayIterator {
    public function __destruct() { echo "  [source destroyed]\n"; }
}

function failing() {
    yield 1;
    throw new Exception('source threw');
}

class FailingAggregate implements IteratorAggregate {
    public function getIterator(): Iterator {
        echo "  [getIterator called]\n";
        throw new Exception('getIterator threw');
    }
}

function source_with_finally(Noisy $guard) {
    try {
        yield 1;
        yield 2;
    } finally {
        echo "  [source finally]\n";
    }
}

$scenarios = [
    'an exception from the source ends the iteration' => function ($create) {
        $it = $create(failing());
        foreach (['current', 'next', 'valid', 'current', 'next'] as $call) {
            try {
                $r = json_encode($it->$call());
            } catch (Exception $e) {
                $r = $e->getMessage();
            }
            echo "  $call: $r\n";
        }
    },
    'getIterator() is called lazily and its exception ends the iteration' => function ($create) {
        $it = $create(new FailingAggregate());
        echo "  created\n";
        foreach (['valid', 'valid'] as $call) {
            try {
                $r = json_encode($it->$call());
            } catch (Exception $e) {
                $r = $e->getMessage();
            }
            echo "  $call: $r\n";
        }
    },
    'the source is released as soon as the iteration ends' => function ($create) {
        // Not a generator: a finished generator releases its arguments by itself.
        $it = $create(new NoisyIterator([1, 2]));
        foreach ($it as $v) {
            echo "  got $v\n";
        }
        echo "  loop done\n";
        unset($it);
        echo "  iterator released\n";
    },
    'an unfinished source is released with the iterator' => function ($create) {
        $it = $create(source_with_finally(new Noisy('guard')));
        echo "  current: ", $it->current(), "\n";
        unset($it);
        echo "  iterator released\n";
    },
];

foreach ($scenarios as $name => $scenario) {
    echo "-- $name\n";
    $results = [];
    foreach (['polyfill' => polyfill_values(...), 'native' => iterator_values(...)] as $impl => $create) {
        ob_start();
        $scenario($create);
        $results[$impl] = ob_get_clean();
    }
    echo $results['native'];
    echo $results['polyfill'] === $results['native'] ? "  => same as polyfill\n" : "  => DIFFERENT, polyfill:\n" . $results['polyfill'];
}

?>
--EXPECT--
-- an exception from the source ends the iteration
  current: 1
  next: source threw
  valid: false
  current: null
  next: null
  => same as polyfill
-- getIterator() is called lazily and its exception ends the iteration
  created
  [getIterator called]
  valid: getIterator threw
  valid: false
  => same as polyfill
-- the source is released as soon as the iteration ends
  got 1
  got 2
  [source destroyed]
  loop done
  iterator released
  => same as polyfill
-- an unfinished source is released with the iterator
  current: 1
  [source finally]
  [guard destroyed]
  iterator released
  => same as polyfill
