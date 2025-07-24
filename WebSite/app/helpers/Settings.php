<?php

namespace app\helpers;

class Settings extends Data
{
    public function get_(string $name): string
    {
        $query = $this->pdo->prepare('SELECT Value FROM Settings WHERE Name = ?');
        $query->execute([$name]);
        return $query->fetch()->Value ?? null;
    }

    public function set_(string $name, string $value): void
    {
        $query = $this->pdo->prepare('UPDATE Settings SET Value = ? WHERE Name = ?');
        $query->execute([$value, $name]);
    }
}
