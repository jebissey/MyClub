<?php

namespace app\interfaces;

use app\valueObjects\ApiResponse;

interface NeedTypeServiceInterface
{
    public function deleteNeedType(int $id): ApiResponse;
    public function saveNeedType(array $data): ApiResponse;
}
