<?php

declare(strict_types=1);

namespace test\Core\ValueObjects;

final readonly class Simulation
{
    /**
     * @param int                       $dbId   Id of the row in the Test table
     * @param int                       $number Value of the Step column (unique)
     * @param array<string, mixed>      $getParams
     * @param array<string, mixed>      $postParams
     * @param array<string, mixed>|null $connectedUser
     */
    public function __construct(
        public Route $route,
        public int $dbId,
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
     *     Id: int,
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
            'Id' => $this->dbId,
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
