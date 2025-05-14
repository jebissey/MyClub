<?php

namespace app\helpers;

use PDO;

class BaseHelper
{
    protected PDO $pdo;
    protected $mediaPath;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function getGroups()
    {
        $query = $this->pdo->query("SELECT * FROM 'Group' WHERE Inactivated = 0 ORDER BY Name");
        return $query->fetchAll();
    }

    protected function getPersonByEmail($email)
    {
        $query = $this->pdo->prepare('SELECT * FROM "Person" WHERE Email = ? COLLATE NOCASE');
        $query->execute([$email]);
        return $query->fetch();
    }

    protected function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . $host . '/';

        return $baseUrl;
    }
}
