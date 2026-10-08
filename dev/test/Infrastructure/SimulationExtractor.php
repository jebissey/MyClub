<?php

declare(strict_types=1);

namespace test\Infrastructure;

use RuntimeException;
use Throwable;

use test\Core\ValueObjects\Route;
use test\Core\ValueObjects\Simulation;
use test\Interfaces\TestDataRepositoryInterface;

final class SimulationExtractor
{
    public function __construct(private TestDataRepositoryInterface $repo) {}

    /**
     * Extracts every simulation (Step IS NOT NULL) from the test database.
     * The simulation number is the value of the Step column (unique);
     * the dbId is the Id of the row, used to find it back in the database.
     *
     * @return list<Simulation>
     */
    public function extract(): array
    {
        $data = $this->repo->getSimulations();
        $simulations = [];

        /** @var array{
         *     Id: int|string,
         *     Method: string,
         *     Uri: string,
         *     Step: int|string,
         *     JsonGetParameters: string|null,
         *     JsonPostParameters: string|null,
         *     JsonConnectedUser: string|null,
         *     ExpectedResponseCode: int|string,
         *     Query: string|null,
         *     QueryExpectedResponse: string|null,
         * } $row
         */
        foreach ($data as $row) {
            try {
                $simulations[] = new Simulation(
                    route: new Route(
                        method: $row['Method'],
                        path: $row['Uri'],
                        hasParameters: str_contains($row['Uri'], '@'),
                    ),
                    dbId: (int) $row['Id'],
                    number: (int) $row['Step'],
                    getParams: $this->decodeArray($row['JsonGetParameters'] ?? null),
                    postParams: $this->decodeArray($row['JsonPostParameters'] ?? null),
                    connectedUser: $this->decodeNullableArray($row['JsonConnectedUser'] ?? null),
                    expectedResponseCode: (int) $row['ExpectedResponseCode'],
                    query: $row['Query'],
                    queryExpectedResponse: $row['QueryExpectedResponse'],
                );
            } catch (Throwable $e) {
                throw new RuntimeException(
                    'error: ' . $e->getMessage()
                    . ' on dbId ' . $row['Id']
                    . ' (Step ' . $row['Step'] . ') '
                    . $row['Method'] . ' ' . $row['Uri']
                );
            }
        }

        return $simulations;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeArray(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeNullableArray(?string $json): ?array
    {
        if ($json === null || $json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return null;
        }

        foreach (array_keys($decoded) as $key) {
            if (!is_string($key)) {
                return null;
            }
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
