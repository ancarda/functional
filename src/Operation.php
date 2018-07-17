<?php

declare(strict_types=1);

namespace Ancarda\Functional;

use \Countable;
use \LogicException;

/**
 * A chain of ordered modifications to some data.
 *
 * An Operation is a collection of steps ("modifiers") to apply
 * to some initial data. Modifiers should ingest data, transform
 * it somehow, and return data. They should be free of
 * side-effects wherever possible. Some modifiers are
 * pre-defined, such as `->sort()` and some modifiers accept a
 * user defined callback that can let you supply the engine for
 * that modification.
 *
 * While the majority of modifiers can work with a variety of
 * types, some are very type sensitive. Please read the
 * description of each modifier carefully before use.
 *
 * Modifiers are stacked up in a chain, for example:
 *
 *     $op = new Operation;
 *     $op = $op->input([1, 2, 3])->reverse();
 *
 * `$op` is now an Operation that describes a program that,
 * when realized (executed), will return `[3, 2, 1]`.
 *
 * In this class, each function, aside from `input` and
 * `realize` returns an instance of Operation that contains the
 * function you wish to run. Operations execute once realize()
 * is called. They need an initial value given by input(),
 * otherwise `null` will be passed to the first modifier.
 *
 * Calling realize() with no functions will return the initial
 * value, or null if there is no initial value.
 */
class Operation
{
    private $initial = null;
    private $operations = [];

    /**
     * Sets the value to be given to the initial modifier.
     *
     * @param mixed $initial
     * @return Operation
     */
    public function input($initial): self
    {
        $clone = clone $this;
        $clone->initial = $initial;
        return $clone;
    }

    /**
     * Filter elements in an array.
     *
     * Pushes a modifier that uses PHP's array_filter function
     * that takes a user-supplied callback of the signature:
     *
     *     function (mixed $value): bool
     *
     * Where this callback returns false, the item in the array
     * will be dropped. This modifier can cause type errors to
     * appear if the previous modifier in the operation chain
     * does not return an array.
     *
     * This modifier returns an array.
     *
     * @param callable $cb (mixed) -> bool
     * @return Operation
     */
    public function filter(callable $cb): self
    {
        $clone = clone $this;
        $clone->operations[] = function ($value) use ($cb): array {
            return array_filter($value, $cb);
        };
        return $clone;
    }

    /**
     * Modify elements in an array.
     *
     * Pushes a modifier that uses PHP's array_map function that
     * takes a user-supplied callback of the signature:
     *
     *     function (mixed $value): mixed
     *
     * Where this callback's return value is used to replace the
     * current item in the array's value. This modifier can
     * cause type errors to appear if the previous modifier in
     * the operation chain does not return an array.
     *
     * This modifier returns an array.
     *
     * @param callable $cb (mixed) -> mixed
     * @return Operation
     */
    public function modify(callable $cb): self
    {
        $clone = clone $this;
        $clone->operations[] = function ($value) use ($cb): array {
            return array_map($cb, $value);
        };
        return $clone;
    }

    /**
     * Remove duplicate values in an array.
     *
     * Pushes a modifier that uses PHP's array_unique function.
     *
     * This modifier can cause type errors to appear if the
     * previous modifier in the operation chain does not return
     * an array.
     *
     * This modifier returns an array.
     *
     * @return Operation
     */
    public function deduplicate(): self
    {
        $clone = clone $this;
        $clone->operations[] = function (array $value): array {
            return array_unique($value);
        };
        return $clone;
    }

    /**
     * Determines length.
     *
     * Pushes a modifier that uses strlen for byte strings or
     * count for arrays and anything implementing Countable.
     * Array count is shallow.
     *
     * This function should not be used to calculate the length
     * of Unicode text. The acceptance of strings is intended
     * for binary data or ASCII text processing only.
     *
     * This modifier returns an int.
     *
     * This modifier throws \LogicException if the value on the
     * stack is not possible to convert to length (int).
     *
     * @return Operation
     */
    public function length(): self
    {
        $clone = clone $this;
        $clone->operations[] = function ($value): int {
            if (is_string($value)) {
                return strlen($value);
            } elseif (is_array($value)) {
                return count($value);
            } elseif ($value instanceof Countable) {
                return count($value);
            }
            throw new \LogicException('cannot get length for type: ' . gettype($value));
        };
        return $clone;
    }

    /**
     * Appends a value.
     *
     * Pushes a modifier that pushes an array or string on the
     * end of the value.
     *
     * @throws LogicException If type is not array or string.
     * @param string|array $append
     * @return Operation
     */
    public function append($append): self
    {
        $clone = clone $this;
        if (is_array($append)) {
            $clone->operations[] = function ($value) use ($append): array {
                return array_merge($value, $append);
            };
        } elseif (is_string($append)) {
            $clone->operations[] = function ($value) use ($append): string {
                return $value . $append;
            };
        } else {
            throw new \LogicException('cannot append type: ' . gettype($append));
        }
        return $clone;
    }

    /**
     * Prepends a value.
     *
     * Pushes a modifier that pushes an array or string at the
     * beginning of the value.
     *
     * @throws LogicException If type is not array or string.
     * @param string|array $prepend
     * @return Operation
     */
    public function prepend($prepend): self
    {
        $clone = clone $this;
        if (is_array($prepend)) {
            $clone->operations[] = function ($value) use ($prepend): array {
                return array_merge($prepend, $value);
            };
        } elseif (is_string($prepend)) {
            $clone->operations[] = function ($value) use ($prepend): string {
                return $prepend . $value;
            };
        } else {
            throw new \LogicException('cannot prepend type: ' . gettype($prepend));
        }
        return $clone;
    }

    /**
     * Sorts.
     *
     * Pushes a modifier that uses PHP's sort() function to sort
     * the contents if the value on the stack is an array.
     *
     * Because sort() takes a pointer, this modifier copies the
     * input value. It is reccomended, if possible in your
     * application, to use modifiers such as filter before sort
     * to reduce the pain of copying the array.
     *
     * This modifier throws \LogicException if the value on the
     * stack is not possible to sort (eg. not an array).
     *
     * @return Operation
     */
    public function sort(): self
    {
        $clone = clone $this;
        $clone->operations[] = function (array $value): array {
            $ptrSafe = $value;
            sort($ptrSafe);
            return $ptrSafe;
        };
        return $clone;
    }

    /**
     * Shuffles.
     *
     * Pushes a modifier that uses PHP's shuffle() function to
     * shuffle the contents if the value on the stack is an
     * array.
     *
     * Because shuffle() takes a pointer, this modifier copies
     * the input value. It is reccomended, if possible in your
     * application, to use modifiers such as filter before
     * shuffle to reduce the pain of copying the array.
     *
     * @return Operation
     */
    public function shuffle(): self
    {
        $clone = clone $this;
        $clone->operations[] = function (array $value): array {
            $ptrSafe = $value;
            shuffle($ptrSafe);
            return $ptrSafe;
        };
        return $clone;
    }

    /**
     * Reverses the value.
     *
     * Pushes a modifier that uses strrev for byte strings or
     * array_reverse for arrays.
     *
     * This function should not be used to reverse Unicode text.
     * The acceptance of strings is intended for binary data or
     * ASCII text processing only.
     *
     * This modifier's return value is based on it's input. It
     * can return a string or an array.
     *
     * This modifier throws \LogicException if the value on the
     * stack is not possible to reverse (string, array).
     *
     * @return Operation
     */
    public function reverse(): self
    {
        $clone = clone $this;
        $clone->operations[] = function ($value) {
            if (is_array($value)) {
                return array_reverse($value);
            } elseif (is_string($value)) {
                return strrev($value);
            }
            throw new LogicException('cannot reverse value of type: ' . gettype($value));
        };
        return $clone;
    }

    /**
     * Flattens an array hierarchy.
     *
     * Pushes a modifier that flattens the first level of an
     * array, returning an array that contains all items without
     * being nested (at that level). Since this modifier is
     * somewhat difficult to explain in text, here is a diagram:
     *
     *     [ [1, 2], [3, 4] ] --> [1, 2, 3, 4]
     *
     * This modifier returns an array.
     *
     * @return Operation
     */
    public function flatten(): self
    {
        $clone = clone $this;
        $clone->operations[] = function (array $value): array {
            $o = [];
            foreach ($value as $row) {
                if (is_array($row)) {
                    $o = array_merge($o, $row);
                } else {
                    $o[] = $row;
                }
            }
            return $o;
        };
        return $clone;
    }

    /**
     * Execute this operation.
     *
     * @param mixed $input Input to use for this specific call.
     * @return mixed Value from the last modifier.
     * @throws Exception Various Exceptions from Modifiers
     */
    public function realize($input = null)
    {
        $value = $input === null ? $this->initial : $input;

        foreach ($this->operations as $operation) {
            $value = $operation($value);
        }

        return $value;
    }
}
