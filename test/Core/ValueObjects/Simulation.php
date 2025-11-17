<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

readonly class Simulation
{
    public function __construct(
        public Route $route,
        public int $number,
        public array $getParams,
        public array $postParams,
        public ?array $connectedUser,
        public int $expectedResponseCode,
        public ?string $query,
        public ?string $queryExpectedResponse
    ) {}

    public function toArray(): array
    {
        return [
            'Method' => $this->route->method,
            'Uri' => $this->route->originalPath,
            'Step' => $this->number,
            'JsonGetParameters' => json_encode($this->getParams),
            'JsonPostParameters' => json_encode($this->postParams),
            'JsonConnectedUser' => $this->connectedUser == null ? null : json_encode($this->connectedUser),
            'ExpectedResponseCode' => $this->expectedResponseCode,
            'Query' => $this->query,
            'QueryExpectedResponse' => $this->queryExpectedResponse,
        ];
    }
}

