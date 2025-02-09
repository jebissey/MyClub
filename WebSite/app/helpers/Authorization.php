<?php

namespace app\helpers;

use PDO;

class Authorization
{
    private PDO $pdo;
    private $authorizations;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get($idPerson)
    {
        $query = $this->pdo->prepare("
            SELECT Authorization.Name FROM Person 
            INNER JOIN PersonGroup ON Person.Id = PersonGroup.IdPerson
            INNER JOIN `Group` ON PersonGroup.IdGroup = `Group`.Id
            INNER JOIN GroupAuthorization on `Group`.Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id 
            WHERE Person.Id = ?");
        $query->execute([$idPerson]);
        return $this->authorizations = $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function isEventManager()
    {
        return in_array('EvnetManager', $this->authorizations);
    }

    public function isPersonManager()
    {
        return in_array('PersonManager', $this->authorizations);
    }

    public function isRedactor()
    {
        return in_array('Redactor', $this->authorizations);
    }

    public function isWebmaster()
    {
        return in_array('Webmaster', $this->authorizations);
    }
}
