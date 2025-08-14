<?php

class SimulationExtractor
{
    public function __construct(private TestDataRepositoryInterface $repo) {}

    public function extract(): array
    {
        $data = $this->repo->getSimulations();
        $simulations = [];
        foreach ($data as $row) {
            $simulations[] = new Simulation(
                route: new Route(
                    method: $row['Method'],
                    originalPath: $row['Uri'],
                    hasParameters: str_contains($row['Uri'], '@'),
                ),
                number: (int) $row['Step'],
                getParams: json_decode($row['JsonGetParameters'] ?? '[]', true),
                postParams: json_decode($row['JsonPostParameters'] ?? '[]', true),
                connectedUser: json_decode($row['JsonConnectedUser'] ?? 'null', true),
                expectedResponseCode: (int) $row['ExpectedResponseCode'],
                query: $row['Query'],
                queryExpectedResponse: $row['QueryExpectedResponse'],
            );
        }
        return $simulations;
    }
}
