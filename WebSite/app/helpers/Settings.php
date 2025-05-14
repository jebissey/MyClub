<?php

namespace app\helpers;

use PDO;

class Settings
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get($name)
    {
        $query = $this->pdo->prepare('SELECT Value FROM Settings WHERE Name = ?');
        $query->execute([$name]);
        return $query->fetch()->Value ?? null;
    }

    public function set($name, $value)
    {
        $query = $this->pdo->prepare('UPDATE Settings SET Value = ? WHERE Name = ?');
        $query->execute([$value, $name]);
    }
}
