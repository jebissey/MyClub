<?php

declare(strict_types=1);

namespace app\config;

final readonly class ControllerFactory
{
    public function __construct(private Container $container)
    {
    }

    /**
     * @template T of object
     * @param class-string<T> $controllerClass
     * @return T
     */
    public function make(string $controllerClass): object
    {
        return $this->container->get($controllerClass);
    }
}
