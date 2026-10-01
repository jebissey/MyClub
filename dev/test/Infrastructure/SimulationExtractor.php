<?php

declare(strict_types=1);

namespace test\Infrastructure;

use test\Core\ValueObjects\Route;
use test\Core\ValueObjects\Simulation;
use test\Interfaces\TestDataRepositoryInterface;
use RuntimeException;
use Throwable;

final class SimulationExtractor
{
    public function __construct(private TestDataRepositoryInterface $repo) {}

    /**
     * Extracts every simulation (Step IS NOT NULL) from the test database.
     * The simulation number is the value of the Step column.
     *
     * @return list<Simulation>
     */
    public function extract(): array
    {
        $data = $this->repo->getSimulations();
        $simulations = [];
        foreach ($data as $row) {
            try {
                $simulations[] = new Simulation(
                    route: new Route(
                        method: $row['Method'],
                        path: $row['Uri'],
                        hasParameters: str_contains($row['Uri'], '@'),
                    ),
                    number: (int) $row['Step'],
                    getParams: json_decode($row['JsonGetParameters'] ?? '[]', true),
                    postParams: json_decode($row['JsonPostParameters'] ?? '[]', true),
                    connectedUser: $row['JsonConnectedUser'] == null ? null : json_decode($row['JsonConnectedUser'], true),
                    expectedResponseCode: (int) $row['ExpectedResponseCode'],
                    query: $row['Query'],
                    queryExpectedResponse: $row['QueryExpectedResponse'],
                );
            } catch (Throwable $e) {
                throw new RuntimeException('error: ' . $e->getMessage() . ' on Step ' . $row['Step'] . ' ' . $row['Method'] . ' ' . $row['Uri']);
            }
        }

        return $simulations;
    }
}