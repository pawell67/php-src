--TEST--
iterator_reduce/any/all/find/find_key/first(): exceptions and argument errors
--FILE--
<?php

function gen() {
    yield 'a' => 1;
    yield 'b' => 2;
}

$throwing = function ($v) {
    if ($v === 2) {
        throw new Exception("callback threw on $v");
    }
    return false;
};

foreach (['iterator_any', 'iterator_all', 'iterator_find', 'iterator_find_key'] as $fn) {
    foreach ([[1, 2, 3], gen()] as $input) {
        try {
            var_dump($fn($input, fn ($v) => $throwing($v) || $fn === 'iterator_all'));
        } catch (Exception $e) {
            echo "$fn: ", $e->getMessage(), "\n";
        }
    }
}

try {
    iterator_reduce(gen(), fn ($c, $v) => $throwing($v) ?: [$c, $v], 'initial');
} catch (Exception $e) {
    echo "iterator_reduce: ", $e->getMessage(), "\n";
}

echo "-- exception thrown by the iterator itself\n";
function failing() {
    yield 1;
    throw new Exception('iterator threw');
}
foreach (['iterator_any', 'iterator_find', 'iterator_reduce'] as $fn) {
    try {
        $fn(failing(), fn () => false);
    } catch (Exception $e) {
        echo "$fn: ", $e->getMessage(), "\n";
    }
}

echo "-- like array_find(), internal callables reject the extra key argument\n";
foreach (['array_find', 'iterator_find'] as $fn) {
    try {
        $fn(['abc'], strlen(...));
    } catch (ArgumentCountError $e) {
        echo "$fn: ", $e->getMessage(), "\n";
    }
}

echo "-- type errors\n";
try {
    iterator_first(42);
} catch (TypeError $e) {
    echo $e->getMessage(), "\n";
}
try {
    iterator_any([1], 'no_such_function');
} catch (TypeError $e) {
    echo $e->getMessage(), "\n";
}

?>
--EXPECT--
iterator_any: callback threw on 2
iterator_any: callback threw on 2
iterator_all: callback threw on 2
iterator_all: callback threw on 2
iterator_find: callback threw on 2
iterator_find: callback threw on 2
iterator_find_key: callback threw on 2
iterator_find_key: callback threw on 2
iterator_reduce: callback threw on 2
-- exception thrown by the iterator itself
iterator_any: iterator threw
iterator_find: iterator threw
iterator_reduce: iterator threw
-- like array_find(), internal callables reject the extra key argument
array_find: strlen() expects exactly 1 argument, 2 given
iterator_find: strlen() expects exactly 1 argument, 2 given
-- type errors
iterator_first(): Argument #1 ($iterator) must be of type Traversable|array, int given
iterator_any(): Argument #2 ($callback) must be a valid callback, function "no_such_function" not found or invalid function name
