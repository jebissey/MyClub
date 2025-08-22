<?php

namespace test\Interfaces;

interface MyClubDataRepositoryInterface
{
    public function executeQuery(string $query): array;
}

