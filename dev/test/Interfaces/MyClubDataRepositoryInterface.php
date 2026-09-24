<?php

declare(strict_types=1);

namespace test\Interfaces;

interface MyClubDataRepositoryInterface
{
    public function executeQuery(string $query): array;
}

