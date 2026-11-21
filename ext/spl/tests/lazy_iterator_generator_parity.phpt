--TEST--
LazyIterator: method calls behave like a generator wrapping foreach (the userland polyfill)
--FILE--
<?php

// What the userland polyfill of iterator_values() returns.
function polyfill_values(iterable $iterator): Generator {
    foreach ($iterator as $value) {
        yield $value;
    }
}

function source() {
    echo "  [source starts]\n";
    yield 'a' => 1;
    echo "  [source resumes]\n";
    yield 'b' => 2;
    echo "  [source resumes]\n";
}

// Each scenario is a list of method calls on a fresh iterator.
$scenarios = [
    'nothing is pulled on creation' => [],
    'current() starts the iteration' => ['current', 'key', 'current'],
    'rewind() before and at the first entry' => ['rewind', 'rewind', 'current', 'rewind'],
    'next() on a fresh iterator moves to the second entry' => ['next', 'current', 'key'],
    'rewind() after next() throws' => ['current', 'next', 'rewind', 'current'],
    'calls after the end' => ['next', 'next', 'valid', 'current', 'key', 'next', 'rewind'],
];

foreach ($scenarios as $name => $calls) {
    echo "-- $name\n";
    $results = [];
    foreach (['polyfill' => polyfill_values(...), 'native' => iterator_values(...)] as $impl => $create) {
        ob_start();
        $it = $create(source());
        foreach ($calls as $call) {
            try {
                $r = $it->$call();
                echo "  $call: ", json_encode($r), "\n";
            } catch (Exception $e) {
                echo "  $call: ", get_class($e), "\n";
            }
        }
        $results[$impl] = ob_get_clean();
    }
    echo $results['native'];
    echo $results['polyfill'] === $results['native'] ? "  => same as polyfill\n" : "  => DIFFERENT, polyfill:\n" . $results['polyfill'];
}

?>
--EXPECT--
-- nothing is pulled on creation
  => same as polyfill
-- current() starts the iteration
  [source starts]
  current: 1
  key: 0
  current: 1
  => same as polyfill
-- rewind() before and at the first entry
  [source starts]
  rewind: null
  rewind: null
  current: 1
  rewind: null
  => same as polyfill
-- next() on a fresh iterator moves to the second entry
  [source starts]
  [source resumes]
  next: null
  current: 2
  key: 1
  => same as polyfill
-- rewind() after next() throws
  [source starts]
  current: 1
  [source resumes]
  next: null
  rewind: Exception
  current: 2
  => same as polyfill
-- calls after the end
  [source starts]
  [source resumes]
  next: null
  [source resumes]
  next: null
  valid: false
  current: null
  key: null
  next: null
  rewind: Exception
  => same as polyfill
