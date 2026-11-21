--TEST--
Composed lazy pipeline over an infinite generator pulls only what is needed
--FILE--
<?php

$pulls = 0;
$naturals = (function () use (&$pulls) {
    for ($i = 1; ; $i++) {
        $pulls++;
        yield $i;
    }
})();

$evens = iterator_filter($naturals, fn (int $x) => $x % 2 === 0);
$squares = iterator_map($evens, fn (int $x) => $x * $x);
$firstThree = iterator_slice($squares, 0, 3);

echo json_encode(iterator_to_array($firstThree, false)), "\n";
echo "pulls: $pulls\n";
?>
--EXPECT--
[4,16,36]
pulls: 6
