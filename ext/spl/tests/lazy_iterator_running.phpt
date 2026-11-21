--TEST--
LazyIterator: cannot be advanced from within its own iteration
--FILE--
<?php

class Source implements Iterator {
    public ?Iterator $lazy = null;
    private int $i = 0;
    public function current(): mixed {
        if ($this->i === 1) {
            try {
                $this->lazy->next();
            } catch (Error $e) {
                echo get_class($e), ": ", $e->getMessage(), "\n";
            }
            // Reading the iterator is allowed, like on a running generator.
            var_dump($this->lazy->valid());
        }
        return $this->i;
    }
    public function key(): mixed { return $this->i; }
    public function next(): void { $this->i++; }
    public function rewind(): void { $this->i = 0; }
    public function valid(): bool { return $this->i < 3; }
}

$source = new Source();
$source->lazy = iterator_values($source);
var_dump(iterator_to_array($source->lazy));

?>
--EXPECT--
Error: Cannot resume an already running LazyIterator
bool(true)
array(3) {
  [0]=>
  int(0)
  [1]=>
  int(1)
  [2]=>
  int(2)
}
