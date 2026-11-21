--TEST--
iterator_map()/iterator_filter(): callback edge cases behave like the userland polyfill
--FILE--
<?php

// The userland polyfill of iterator_map() and iterator_filter().
function polyfill_map(iterable $iterator, callable $callback): Generator {
    foreach ($iterator as $key => $value) {
        yield $key => $callback($value);
    }
}
function polyfill_filter(iterable $iterator, ?callable $callback = null, int $mode = ARRAY_FILTER_USE_VALUE): Generator {
    foreach ($iterator as $key => $value) {
        if ($callback === null) {
            $keep = $value;
        } elseif ($mode === ARRAY_FILTER_USE_KEY) {
            $keep = $callback($key);
        } elseif ($mode === ARRAY_FILTER_USE_BOTH) {
            $keep = $callback($value, $key);
        } else {
            $keep = $callback($value);
        }
        if ($keep) {
            yield $key => $value;
        }
    }
}

class Noisy {
    public function __construct(public string $name) {}
    public function __destruct() { echo "  [{$this->name} destroyed]\n"; }
}

class Magic {
    public function __call($name, $args) { return "$name(" . implode(', ', array_map(json_encode(...), $args)) . ")"; }
}

function gen() {
    yield 'a' => str_repeat('x', 3);
    yield 'b' => str_repeat('y', 3);
    yield 'c' => str_repeat('z', 3);
}

function nonscalar_keys() {
    yield [1] => 'array key';
    yield new ArrayObject() => 'object key';
    yield null => 'null key';
}

function dump(iterable $it) {
    try {
        foreach ($it as $k => $v) {
            echo "  ", json_encode($k), " => ", json_encode($v), "\n";
        }
    } catch (Exception $e) {
        echo "  ", get_class($e), ": ", $e->getMessage(), "\n";
    }
}

$scenarios = [
    'map: the callback throws' => fn ($map, $filter) => dump($map(gen(), function ($v) {
        if ($v === 'yyy') {
            throw new Exception('callback threw');
        }
        return strtoupper($v);
    })),
    'filter: the callback throws' => fn ($map, $filter) => dump($filter(gen(), function ($v) {
        if ($v === 'yyy') {
            throw new Exception('callback threw');
        }
        return true;
    })),
    'map: the callback advances the source' => function ($map, $filter) {
        $g = gen();
        dump($map($g, function ($v) use ($g) {
            $g->next();
            return $v;
        }));
    },
    'filter: the callback advances the source' => function ($map, $filter) {
        $g = gen();
        dump($filter($g, function ($v) use ($g) {
            $g->next();
            return true;
        }));
    },
    'map: a by-reference callback yields plain values' => function ($map, $filter) {
        $x = 'v';
        $it = $map([1, 2], function &($v) use (&$x) {
            return $x;
        });
        foreach ($it as $v) {
            $v .= '!';
        }
        echo "  x is still ", json_encode($x), "\n";
        // Not through foreach, which would dereference the value anyway.
        $array = iterator_to_array($map([1], function &($v) use (&$x) {
            return $x;
        }));
        $array[0] .= '!';
        echo "  x is still ", json_encode($x), "\n";
        $it = $map([1], function &($v) use (&$x) {
            return $x;
        });
        $current = $it->current();
        $current .= '!';
        echo "  x is still ", json_encode($x), "\n";
    },
    'map/filter: __call callables' => function ($map, $filter) {
        dump($map(['k' => 1, 'l' => 2], [new Magic(), 'm']));
        dump($filter(['k' => 1], [new Magic(), 'f'], ARRAY_FILTER_USE_BOTH));
    },
    'filter: no callback keeps truthy values' => fn ($map, $filter) => dump($filter([0, 1, '', 'a', null, [], [0], '0'])),
    'filter: modes with non-scalar keys' => function ($map, $filter) {
        $show = function (...$args) {
            echo "  callback(", implode(', ', array_map(get_debug_type(...), $args)), ")\n";
            return true;
        };
        foreach ([ARRAY_FILTER_USE_VALUE, ARRAY_FILTER_USE_KEY, ARRAY_FILTER_USE_BOTH] as $mode) {
            foreach ($filter(nonscalar_keys(), $show, $mode) as $_) {
            }
        }
    },
    'map/filter: the callback is released when the iteration ends' => function ($map, $filter) {
        foreach (['map' => $map, 'filter' => $filter] as $name => $fn) {
            $guard = new Noisy("$name callback");
            $it = $fn([1, 2], function ($v) use ($guard) { return $v; });
            unset($guard);
            foreach ($it as $_) {
            }
            echo "  $name loop done\n";
        }
    },
];

foreach ($scenarios as $name => $scenario) {
    echo "-- $name\n";
    $results = [];
    foreach (['polyfill' => [polyfill_map(...), polyfill_filter(...)], 'native' => [iterator_map(...), iterator_filter(...)]] as $impl => [$map, $filter]) {
        ob_start();
        $scenario($map, $filter);
        $results[$impl] = ob_get_clean();
    }
    echo $results['native'];
    echo $results['polyfill'] === $results['native'] ? "  => same as polyfill\n" : "  => DIFFERENT, polyfill:\n" . $results['polyfill'];
}

echo "-- invalid mode is rejected at call time\n";
try {
    iterator_filter([], fn () => true, 42);
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}
try {
    iterator_filter([], [new Magic(), 'f'], 42);
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}

echo "-- a callback cannot advance the iterator it is running in\n";
$it = iterator_map([1, 2], function ($v) use (&$it) {
    try {
        $it->next();
    } catch (Error $e) {
        echo $e->getMessage(), "\n";
    }
    return $v;
});
var_dump(iterator_to_array($it));

?>
--EXPECT--
-- map: the callback throws
  "a" => "XXX"
  Exception: callback threw
  => same as polyfill
-- filter: the callback throws
  "a" => "xxx"
  Exception: callback threw
  => same as polyfill
-- map: the callback advances the source
  "a" => "xxx"
  "c" => "zzz"
  => same as polyfill
-- filter: the callback advances the source
  "a" => "xxx"
  "c" => "zzz"
  => same as polyfill
-- map: a by-reference callback yields plain values
  x is still "v"
  x is still "v"
  x is still "v"
  => same as polyfill
-- map/filter: __call callables
  "k" => "m(1)"
  "l" => "m(2)"
  "k" => 1
  => same as polyfill
-- filter: no callback keeps truthy values
  1 => 1
  3 => "a"
  6 => [0]
  => same as polyfill
-- filter: modes with non-scalar keys
  callback(string)
  callback(string)
  callback(string)
  callback(array)
  callback(ArrayObject)
  callback(null)
  callback(string, array)
  callback(string, ArrayObject)
  callback(string, null)
  => same as polyfill
-- map/filter: the callback is released when the iteration ends
  [map callback destroyed]
  map loop done
  [filter callback destroyed]
  filter loop done
  => same as polyfill
-- invalid mode is rejected at call time
iterator_filter(): Argument #3 ($mode) must be one of ARRAY_FILTER_USE_VALUE, ARRAY_FILTER_USE_KEY, or ARRAY_FILTER_USE_BOTH
iterator_filter(): Argument #3 ($mode) must be one of ARRAY_FILTER_USE_VALUE, ARRAY_FILTER_USE_KEY, or ARRAY_FILTER_USE_BOTH
-- a callback cannot advance the iterator it is running in
Cannot resume an already running LazyIterator
Cannot resume an already running LazyIterator
array(2) {
  [0]=>
  int(1)
  [1]=>
  int(2)
}
