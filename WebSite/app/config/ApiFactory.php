<?php

declare(strict_types=1);

namespace app\config;

/**
 * Builds API classes on demand.
 *
 * Dependencies are resolved by the container from the constructor types,
 * so adding a dependency to an API never requires touching this class.
 */
final readonly class ApiFactory
{
    public function __construct(private Container $container)
    {
    }

    /**
     * @template T of object
     * @param class-string<T> $apiClass
     * @return T
     */
    public function make(string $apiClass): object
    {
        return $this->container->get($apiClass);
    }
}
