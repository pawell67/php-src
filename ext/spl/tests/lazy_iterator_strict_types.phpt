--TEST--
iterator_map()/iterator_filter(): callbacks use coercive typing, like array_map(), even under strict_types
--FILE--
<?php
declare(strict_types=1);

$intOnly = fn (int $x) => $x * 2;
var_dump(iterator_to_array(iterator_map(['1', '2'], $intOnly)) === array_map($intOnly, ['1', '2']));
var_dump(iterator_to_array(iterator_filter(['1', '0'], $intOnly)) === array_filter(['1', '0'], $intOnly));

try {
    iterator_map(['x'], $intOnly)->current();
} catch (TypeError $e) {
    echo $e->getMessage(), "\n";
}

?>
--EXPECTF--
bool(true)
bool(true)
{closure:%s:%d}(): Argument #1 ($x) must be of type int, string given
