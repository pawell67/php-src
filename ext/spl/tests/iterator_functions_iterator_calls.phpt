--TEST--
iterator_* functions call the same Iterator methods as a foreach over the input
--FILE--
<?php

/* Records the methods called on it. Each function must make the same calls as
 * the equivalent foreach: key() only when the key is used. */
class LoggingIterator implements Iterator
{
    public string $log = '';
    private int $i = 0;

    public function rewind(): void { $this->log .= 'R'; $this->i = 0; }
    public function valid(): bool { $this->log .= 'V'; return $this->i < 2; }
    public function current(): mixed { $this->log .= 'C'; return $this->i * 10; }
    public function key(): mixed { $this->log .= 'K'; return "k$this->i"; }
    public function next(): void { $this->log .= 'N'; $this->i++; }
}

$cases = [
    'map' => fn ($it) => iterator_to_array(iterator_map($it, fn ($v) => $v)),
    'filter' => fn ($it) => iterator_to_array(iterator_filter($it, fn ($v) => true)),
    'filter (no callback)' => fn ($it) => iterator_to_array(iterator_filter($it)),
    'filter (by key)' => fn ($it) => iterator_to_array(iterator_filter($it, fn ($k) => true, ARRAY_FILTER_USE_KEY)),
    'slice' => fn ($it) => iterator_to_array(iterator_slice($it, 1)),
    'keys' => fn ($it) => iterator_to_array(iterator_keys($it)),
    'values' => fn ($it) => iterator_to_array(iterator_values($it)),
    'reduce' => fn ($it) => iterator_reduce($it, fn ($c, $v) => $c + $v, 0),
    'any' => fn ($it) => iterator_any($it, fn ($v) => false),
    'all' => fn ($it) => iterator_all($it, fn ($v) => true),
    'find' => fn ($it) => iterator_find($it, fn ($v) => false),
    'find_key' => fn ($it) => iterator_find_key($it, fn ($v) => false),
    'first' => fn ($it) => iterator_first($it),
];

foreach ($cases as $name => $case) {
    $it = new LoggingIterator();
    $case($it);
    echo str_pad($name, 21), $it->log, "\n";
}

echo "\nforeach (\$it as \$v):       ";
$it = new LoggingIterator();
foreach ($it as $v);
echo $it->log, "\n";
echo "foreach (\$it as \$k => \$v): ";
$it = new LoggingIterator();
foreach ($it as $k => $v);
echo $it->log, "\n";

?>
--EXPECT--
map                  RVCKNVCKNV
filter               RVCKNVCKNV
filter (no callback) RVCKNVCKNV
filter (by key)      RVCKNVCKNV
slice                RVCKNVCKNV
keys                 RVCKNVCKNV
values               RVCNVCNV
reduce               RVCNVCNV
any                  RVCKNVCKNV
all                  RVCKNVCKNV
find                 RVCKNVCKNV
find_key             RVCKNVCKNV
first                RVC

foreach ($it as $v):       RVCNVCNV
foreach ($it as $k => $v): RVCKNVCKNV
