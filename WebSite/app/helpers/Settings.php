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
        return $this->getFromSettings('Help_home');
    }

    public function getHelpUser()
    {
        return $this->getFromSettings('Help_user');
    }

    public function getHelpAdmin()
    {
        return $this->getFromSettings('Help_admin');
    }

    public function getHelpWebmaster()
    {
        return $this->getFromSettings('Help_webmaster');
    }

    public function getHelpEventManager()
    {
        return $this->getFromSettings('Help_eventManager');
    }

    public function getHelpPersonManager()
    {
        return $this->getFromSettings('Help_personManager');
    }

    public function getLegalNotices()
    {
        return $this->getFromSettings('LegalNotices');
    }

    
    private function getFromSettings($name)
    {
        $query = $this->pdo->prepare("SELECT Value FROM Settings WHERE Name = '$name'");
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC)['Value'];
    }
}
