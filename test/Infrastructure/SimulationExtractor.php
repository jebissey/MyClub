<?php

namespace test\Infrastructure;

use test\Core\Route;
use test\Core\Simulation;
use test\Interfaces\TestDataRepositoryInterface;
use RuntimeException;
use Throwable;

class SimulationExtractor
{
    public function __construct(private TestDataRepositoryInterface $repo) {}

    public function extract(): array
    {
        $data = $this->repo->getSimulations();
        $simulations = [];
        foreach ($data as $row) {
            try {
                $simulations[] = new Simulation(
                    route: new Route(
                        method: $row['Method'],
                        originalPath: $row['Uri'],
                        hasParameters: str_contains($row['Uri'], '@'),
                        testedPath: $row['Uri']
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
                throw new RuntimeException('error: ' .  $e->getMessage() . ' on Step ' . $row['Step'] . ' ' . $row['Method'] . ' ' . $row['Uri']);
            }
        }
        return $simulations;
    }
}
