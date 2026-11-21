--TEST--
iterator_find_key(): exception thrown when the iterator is destroyed after the result is known
--FILE--
<?php

// The generator is created inside getIterator(), so the engine's iterator holds
// the only reference to it and destroys it inside iterator_find_key(), after the
// result has been written to the return value. Key and value are built at
// runtime so that they are refcounted. This case is kept alone in its own file:
// a double free of the result is only reliably detected on a fresh heap.
class ThrowsInFinally implements IteratorAggregate {
    public function getIterator(): Generator {
        try {
            yield str_repeat('k', 3) => str_repeat('v', 3);
            yield 'other' => 'never reached';
        } finally {
            throw new Exception('thrown in finally');
        }
    }
}

try {
    var_dump(iterator_find_key(new ThrowsInFinally, fn () => true));
} catch (Exception $e) {
    echo $e->getMessage(), "\n";
}

?>
--EXPECT--
thrown in finally
