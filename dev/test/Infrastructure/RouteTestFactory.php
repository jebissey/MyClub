<?php

declare(strict_types=1);

namespace test\Infrastructure;

use Throwable;

use test\Core\ConsoleTestReporter;
use test\Core\TestDataValidator;
use test\Core\TestExecutor;
use test\Core\ValueObjects\TestConfiguration;
use test\Database\SqliteMyClubDataRepository;
use test\Database\SqliteTestDataRepository;

final class RouteTestFactory
{
    public static function create(
        TestConfiguration $config,
        ?string $dbTestsPath = null,
        ?string $dbMyClubPath = null
    ): RouteTestOrchestrator {
        $httpClient = new CurlHttpClient($config);
        $reporter = new ConsoleTestReporter();

        if ($dbTestsPath === null || !file_exists($dbTestsPath)) {
            $hint = $dbTestsPath !== null
                ? "Specified path: {$dbTestsPath} (exists: no)"
                : 'No tests database path provided';
            throw new \InvalidArgumentException(
                "Tests database is required but missing or not found. {$hint}"
            );
        }

        try {
            $testDataRepository = new SqliteTestDataRepository($dbTestsPath);
        } catch (Throwable $e) {
            throw new \RuntimeException(
                'Failed to connect to tests database: ' . $e->getMessage(),
                0,
                $e
            );
        }

        $myClubDataRepository = new SqliteMyClubDataRepository($dbMyClubPath);

        return new RouteTestOrchestrator(
            new FlightRouteExtractor(),
            new SimulationExtractor($testDataRepository),
            new TestExecutor(
                $testDataRepository,
                $myClubDataRepository,
                new SessionAuthenticator($httpClient, '/user/sign/in'),
                $httpClient,
                new TestDataValidator(),
                $reporter,
                $config
            ),
            $reporter,
            $dbTestsPath
        );
    }
}
