<?php

declare(strict_types=1);

namespace app\config;

use Closure;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Minimal autowiring container.
 * Every object is built once, on first request, from the types of its constructor.
 * Only objects needing scalars, singletons or special setup require an explicit definition.
 */
final class Container
{
    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, Closure(self): object> */
    private array $definitions = [];

    /** @var array<string, true> */
    private array $resolving = [];

    public function set(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * @param Closure(self): object $factory
     */
    public function define(string $id, Closure $factory): void
    {
        $this->definitions[$id] = $factory;
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            /** @var T */
            return $this->instances[$id];
        }
        if (isset($this->resolving[$id])) {
            throw new LogicException(
                'Circular dependency: ' . implode(' -> ', array_keys($this->resolving)) . ' -> ' . $id
            );
        }

        $this->resolving[$id] = true;
        try {
            $instance = isset($this->definitions[$id])
                ? ($this->definitions[$id])($this)
                : $this->autowire($id);
        } finally {
            unset($this->resolving[$id]);
        }

        $this->instances[$id] = $instance;
        /** @var T */
        return $instance;
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new LogicException("Unknown class $class");
        }
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new LogicException("$class is not instantiable: add a definition for it");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                /** @var class-string $dependency */
                $dependency = $type->getName();
                $arguments[] = $this->get($dependency);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } else {
                throw new LogicException(sprintf(
                    'Cannot autowire %s::$%s: add a definition for %s',
                    $class,
                    $parameter->getName(),
                    $class
                ));
            }
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
