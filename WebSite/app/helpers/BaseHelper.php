<?php

namespace app\helpers;

use PDO;

class BaseHelper
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function getGroups()
    {
        $query = $this->pdo->query("SELECT * FROM 'Group' WHERE Inactivated = 0 ORDER BY Name");
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getPersonByEmail($email)
    {
        $query = $this->pdo->prepare('SELECT * FROM "Person" WHERE Email = ? COLLATE NOCASE');
        $query->execute([$email]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
}
