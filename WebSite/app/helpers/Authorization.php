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
            SELECT DISTINCT Authorization.Name FROM Person 
            INNER JOIN PersonGroup ON Person.Id = PersonGroup.IdPerson
            INNER JOIN `Group` ON PersonGroup.IdGroup = `Group`.Id
            INNER JOIN GroupAuthorization on `Group`.Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id 
            WHERE Person.Id = ?");
        $query->execute([$idPerson]);
        return $this->authorizations = array_column($query->fetchAll(), 'Name');
    }


    public function isEventManager(): bool
    {
        return in_array('EventManager', $this->authorizations);
    }

    public function isPersonManager(): bool
    {
        return in_array('PersonManager', $this->authorizations);
    }

    public function isRedactor(): bool
    {
        return in_array('Redactor', $this->authorizations);
    }

    public function isEditor(): bool
    {
        return in_array('Editor', $this->authorizations);
    }

    public function isWebmaster(): bool
    {
        return in_array('Webmaster', $this->authorizations);
    }

    public function hasAutorization() : bool
    {
        return count($this->authorizations ?? []) > 0;
    }

    public function hasOnlyOneAutorization() : bool
    {
        return count($this->authorizations ?? []) == 1;
    }
}
