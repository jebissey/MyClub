<?php

declare(strict_types=1);

namespace app\models;

use PDO;

use app\helpers\Application;

class SharedFileDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getPathsShared(array $paths): array
    {
        if (empty($paths)) return [];

        $stmt = $this->pdo->query("SELECT Item FROM SharedFile WHERE Token IS NOT NULL");
        $items = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $used = [];
        foreach ($paths as $path) {
            foreach ($items as $item) {
                if ($item !== null && str_ends_with($item, $path)) {
                    $used[$path] = true;
                    break;
                }
            }
        }
        return $used;
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
