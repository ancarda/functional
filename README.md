# ancarda/functional

_Functional Programming Helper Functions and Classes_

[![Latest Stable Version](https://poser.pugx.org/ancarda/functional/v/stable)](https://packagist.org/packages/ancarda/functional)
[![Total Downloads](https://poser.pugx.org/ancarda/functional/downloads)](https://packagist.org/packages/ancarda/functional)
[![License](https://img.shields.io/github/license/ancarda/functional.svg)](https://choosealicense.com/licenses/mit/)
[![Build Status](https://travis-ci.com/ancarda/functional.svg?branch=master)](https://travis-ci.com/ancarda/functional)
[![Coverage Status](https://coveralls.io/repos/github/ancarda/functional/badge.svg?branch=master)](https://coveralls.io/github/ancarda/functional?branch=master)

Ancarda\Functional (also known as "_FP Kit_") is a collection of helper functions and classes for PHP 7.0+ that makes basic Functional Programming a little easier. The `Operation` class allows modification to data in a way that is more intuitive and understandable than a series of `array_*` functions.

```php
array_unique(
    array_reduce(
        array_map(
            function (string $path): array {
                return findLinks(__DIR__ . '/' . $path);
            },
            array_filter(
                scandir(__DIR__),
                function (string $path): bool {
                    return strpos($path, '.') !== 0;
                }
            )
        ),
	    function (?array $carry, array $row): array {
		    return array_merge($carry === null ? [] : $carry, $row);
	    }
    )
);
```

This same operation can be done using an `Operation` class. It's far more readable and maintainable. Execution happens once the operation is realized.

```php
(new \Ancarda\Functional\Operation)
    ->input(scandir(__DIR__))
    ->filter(function (string $path): bool {
        return strpos($path, '.') !== 0;
    })
    ->modify(function (string $path): array {
        return findLinks(__DIR__ . '/' . $path);
    })
    ->flatten()
    ->deduplicate()
    ->realize();
```

The key thing that Operation tries to do is make the execution flow more intuitive by making it read top-to-bottom. The PHP snippet above has the initial value (`scandir __DIR__`) in the middle of the code. Not only must one read middle-out (rather than top-down), one must also jump up and down the page, increasingly more so, as more map and reduce calls are made. This is because the operation order isn't consistent.

Furthermore, FP in PHP is held back by some engineering decisions, such as `sort` and `shuffle` taking a pointer. While this is great for performance, it prevents, for instance `sort(range(1, 5))`.

With `Operation`, a lot of this goes away:

 * Modifiers are always operating on the previous computation, therefore most only take a single parameter, if that. It's easier to call them as you don't need to remember any parameter orders.
 * Modifiers for `sort` and `shuffle`, making it easier to create longer chains of functional code.
 * Common, useful constructions like array hierarchy flattening (`flatten`) included out of the box that would otherwise require you to write a reduce callback for that.

If you need different behavior or new modifiers, `Operation` is not a `final` class and is rather easy to extend with your own constructions!

-----

functional (MIT License) can be used with any framework and has no dependencies. This library may be installed via composer with the following command:

	composer require ancarda/functional

For documentation, please run PHPDocumentor on `src/`, or read the source code to see the DocBlocks.
