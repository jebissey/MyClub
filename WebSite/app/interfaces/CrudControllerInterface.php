<?php

namespace app\interfaces;

interface CrudControllerInterface
{
    public function index(): void;
    public function create(): void;
    public function edit(int $id): void;
    public function delete(int $id): void;
}
