<?php

namespace test\Interfaces;

interface RouteExtractorInterface
{
    public function extractRoutes(string $filePath): array;
}

