<?php

namespace app\interfaces;

interface NeedTypeServiceInterface
{
    public function deleteNeedType(int $id): array;
    public function saveNeedType(array $data): array;
}
