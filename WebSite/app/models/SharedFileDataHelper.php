<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class SharedFileDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function isShared(string $path): bool
    {
        $sql = "SELECT 1 
            FROM SharedFile
            WHERE Item LIKE :path AND Token IS NOT NULL
            LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':path' => '%' . $path]);
        return (bool) $stmt->fetchColumn();
    }

    public function getSharedFile(string $path): object | false
    {
        $sql = "SELECT *
            FROM SharedFile
            WHERE Item LIKE :path 
            LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':path' => '%' . $path]);
        return $stmt->fetch();
    }

    public function removeShareFile(string $path): void
    {
        $sql = "UPDATE SharedFile 
                SET Token = null
                WHERE Item LIKE :path";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':path' => '%' . $path]);
    }
}
