--TEST--
LazyIterator: a callback suspending a fiber, like a generator in the polyfill
--FILE--
<?php

function polyfill_map(iterable $iterator, callable $callback): Generator {
    foreach ($iterator as $key => $value) {
        yield $key => $callback($value);
    }
}

$results = [];
foreach (['polyfill' => polyfill_map(...), 'native' => iterator_map(...)] as $impl => $map) {
    ob_start();
    $it = $map([1, 2], function ($v) {
        echo "  callback $v suspends\n";
        $resumed = Fiber::suspend($v);
        echo "  callback $v resumed with $resumed\n";
        return $v * 10;
    });
    $fiber = new Fiber(function () use ($it) {
        foreach ($it as $v) {
            echo "  fiber got $v\n";
        }
        return 'fiber done';
    });

    $suspended = $fiber->start();
    echo "  suspended with $suspended\n";
    try {
        $it->next();
    } catch (Error $e) {
        echo "  next() outside the fiber: ", get_class($e), "\n";
    }
    $suspended = $fiber->resume('a');
    echo "  suspended with $suspended\n";
    $fiber->resume('b');
    echo "  ", $fiber->getReturn(), "\n";

    echo "  -- a fiber destroyed while suspended in the callback\n";
    $it = $map([1, 2], function ($v) {
        try {
            Fiber::suspend();
        } finally {
            echo "  callback finally\n";
        }
        return $v;
    });
    $fiber = new Fiber(function () use ($it) {
        foreach ($it as $_) {
        }
    });
    $fiber->start();
    unset($fiber);
    echo "  fiber destroyed\n";
    var_dump($it->valid());
    $results[$impl] = ob_get_clean();
}
echo $results['native'];
echo $results['polyfill'] === $results['native'] ? "=> same as polyfill\n" : "=> DIFFERENT, polyfill:\n" . $results['polyfill'];

?>
--EXPECT--
  callback 1 suspends
  suspended with 1
  next() outside the fiber: Error
  callback 1 resumed with a
  fiber got 10
  callback 2 suspends
  suspended with 2
  callback 2 resumed with b
  fiber got 20
  fiber done
  -- a fiber destroyed while suspended in the callback
  callback finally
  fiber destroyed
bool(false)
=> same as polyfill
