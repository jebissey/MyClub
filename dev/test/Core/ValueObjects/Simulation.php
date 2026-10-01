<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

final readonly class Simulation
{
    /**
     * @param array<string, mixed>      $getParams
     * @param array<string, mixed>      $postParams
     * @param array<string, mixed>|null $connectedUser
     */
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

    /**
     * @return array{
     *     Method: string,
     *     Uri: string,
     *     Step: int,
     *     JsonGetParameters: string|false,
     *     JsonPostParameters: string|false,
     *     JsonConnectedUser: string|false|null,
     *     ExpectedResponseCode: int,
     *     Query: string|null,
     *     QueryExpectedResponse: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'Method' => $this->route->method,
            'Uri' => $this->route->path,
            'Step' => $this->number,
            'JsonGetParameters' => json_encode($this->getParams),
            'JsonPostParameters' => json_encode($this->postParams),
            'JsonConnectedUser' => $this->connectedUser === null ? null : json_encode($this->connectedUser),
            'ExpectedResponseCode' => $this->expectedResponseCode,
            'Query' => $this->query,
            'QueryExpectedResponse' => $this->queryExpectedResponse,
        ];
    }
}
