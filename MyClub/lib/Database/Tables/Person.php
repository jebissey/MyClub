<?php

require_once __DIR__ . '/../BaseTable.php';

class Person extends BaseTable {

    public function getByEmail($email) {
        $query = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE Email = :email");
        $query->execute(array('email' => $email));
        return $query->fetch(PDO::FETCH_ASSOC);
    }
        
    public function getByName($name) {
        $query = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE FirstName = :name OR LastName = :name OR NickName = :name");
        $query->execute(array('name' => $name));
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getByToken($token) {
        $query = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE Token = :token");
        $query->execute(['token' => $token]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getKeys($id)
    {
        $query = $this->pdo->prepare("
            SELECT Authorization.Id, Authorization.Name FROM Person 
            INNER JOIN PersonGroup ON Person.Id = PersonGroup.IdPerson
            INNER JOIN `Group` ON PersonGroup.IdGroup = `Group`.Id
            INNER JOIN GroupAuthorisation on `Group`.Id = GroupAuthorisation.IdGroup
            INNER JOIN Authorization on GroupAuthorisation.IdAuthorisation = Authorization.Id 
            WHERE Person.Id = :id");
        $query->execute(array('id' => $id));
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    function isUserRegistered($eventId, $userEmail) {
        $query = $this->pdo->prepare("
            SELECT 1
            FROM Participant p
            JOIN Person per ON p.IdPerson = per.Id
            WHERE p.IdEvent = :eventId
            AND per.Email = :userEmail");
        $query->execute([
            'eventId' => $eventId,
            'userEmail' => $userEmail
        ]);
        return $query->fetch() !== false;
    }
}

?>