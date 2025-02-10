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
        return $query->fetch(PDO::FETCH_ASSOC)['Value'];
    }

    public function set($name, $value)
    {
        $query = $this->pdo->prepare('UPDATE Settings SET Value = ? WHERE Name = ?');
        $query->execute([$value, $name]);
    }


    public function getHelpHome()
    {
        $query = $this->pdo->prepare('SELECT Value FROM Settings WHERE Name = "Help_home"');
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC)['Value'];
    }

    public function getHelpUser()
    {
        $query = $this->pdo->prepare('SELECT Value FROM Settings WHERE Name = "Help_user"');
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC)['Value'];
    }

    public function getHelpAdmin()
    {
        $query = $this->pdo->prepare('SELECT Value FROM Settings WHERE Name = "Help_admin"');
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC)['Value'];
    }

    public function getHelpWebmaster()
    {
        $query = $this->pdo->prepare('SELECT Value FROM Settings WHERE Name = "Help_webmaster"');
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC)['Value'];
    }

    public function getLegalNotices()
    {
        $query = $this->pdo->prepare('SELECT Value FROM Settings WHERE Name = "LegalNotices"');
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC)['Value'];
    }
}
