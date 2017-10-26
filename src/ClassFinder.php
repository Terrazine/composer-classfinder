<?php

namespace Terrazine\ComposerClassFinder;

use ArrayIterator;
use Composer\Autoload\ClassLoader;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;

class ClassFinder extends Collection
{
    /**
     * ClassFinder constructor.
     *
     * Fills the collection when constructed without arguments, passes through freely when filtered.
     *
     * @param bool $items
     */
    public function __construct($items = false)
    {
        if (is_bool($items) && $items === false) {
            $items = app(ClassLoader::class)->getClassMap();
        }

        parent::__construct($items);
    }

    /**
     * Get an iterator for the items.
     *
     * appends "|ReflectionClass[]" to the phpdoc return tag of the function::parent
     *
     * @return ArrayIterator|ReflectionClass[]
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Filter collection based on namespace,
     * then boot reflection objects.
     *
     * @param string $namespace
     * @param bool $shouldReflect
     * @return static
     */
    public function namespace(string $namespace, bool $shouldReflect = true) {
        return $this->filter(function ($file, $class) use ($namespace) {
            return Str::startsWith($class, $namespace);
        })->reflect($shouldReflect);
    }

    /**
     * Replace value (path) with ReflectionClass of key.
     *
     * @param bool $shouldReflect
     * @return ClassFinder
     */
    public function reflect(bool $shouldReflect): self {
        if ($shouldReflect) {
            return $this->map(function (string $file, string $class) {
                return new ReflectionClass($class);
            });
        } else {
            return $this;
        }
    }

    /**
     * Wrapper for more eye appealing filters down bellow.
     *
     * @param string $method
     * @param array $arguments
     * @return static
     * @internal
     */
    public function quickFilter(string $method, array $arguments = []) {
        return $this->filter(function (ReflectionClass $reflectionClass) use ($method, $arguments) {
            return call_user_func_array([$reflectionClass, $method], $arguments);
        });
    }

    /**
     * Check if class is subclass of argument.
     *
     * @param string $class
     * @return self
     * @internal
     */
    public function isSubclassOf(string $class): self {
        return $this->quickFilter(
            'isSubclassOf', [$class]
        );
    }

    /**
     * Alias.
     * @see ReflectionClass::isSubclassOf()
     *
     * @param string $class
     * @return self
     */
    public function extends(string $class): self {
        return $this->isSubclassOf($class);
    }

    /**
     * Alias.
     * @see ReflectionClass::isSubclassOf()
     *
     * @param string $class
     * @return self
     */
    public function implements(string $class): self {
        return $this->isSubclassOf($class);
    }

    /**
     * Filter classes that are Instantiable.
     *
     * @return self
     */
    public function isNormal(): self {
        return $this->quickFilter(
            'isInstantiable'
        );
    }

    /**
     * Filter classes that are Traits.
     *
     * @return self
     */
    public function isTrait(): self {
        return $this->quickFilter(
            'isTrait'
        );
    }

    /**
     * Filter classes that are Interfaces.
     *
     * @return self
     */
    public function isInterface(): self {
        return $this->quickFilter(
            'isInterface'
        );
    }
}
