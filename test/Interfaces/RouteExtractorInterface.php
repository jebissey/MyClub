<?php

declare(strict_types=1);

namespace test\Interfaces;

interface RouteExtractorInterface
{
    public function extractRoutes(string $filePath, string $directoryPath): array;
}

