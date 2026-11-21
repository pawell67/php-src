--TEST--
LazyIterator: final, not constructible, not cloneable, not serializable
--FILE--
<?php

$it = iterator_values([1]);
var_dump(get_class($it), $it instanceof Iterator);

$rc = new ReflectionClass(LazyIterator::class);
var_dump($rc->isFinal(), $rc->isInternal());

try {
    new LazyIterator();
} catch (Error $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}
try {
    $rc->newInstanceWithoutConstructor();
} catch (ReflectionException $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}
try {
    clone $it;
} catch (Error $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}
try {
    serialize($it);
} catch (Exception $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}
try {
    unserialize('O:12:"LazyIterator":0:{}');
} catch (Exception $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}
try {
    $it->dynamic = 1;
} catch (Error $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}
try {
    foreach (iterator_values([1]) as &$value) {}
} catch (Error $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}

?>
--EXPECT--
string(12) "LazyIterator"
bool(true)
bool(true)
bool(true)
Error: Call to private LazyIterator::__construct() from global scope
ReflectionException: Class LazyIterator is an internal class marked as final that cannot be instantiated without invoking its constructor
Error: Trying to clone an uncloneable object of class LazyIterator
Exception: Serialization of 'LazyIterator' is not allowed
Exception: Unserialization of 'LazyIterator' is not allowed
Error: Cannot create dynamic property LazyIterator::$dynamic
Error: An iterator cannot be used with foreach by reference
