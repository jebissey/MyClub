<?php

require_once __DIR__ . '/../BaseTable.php';

class Group extends BaseTable {

    public function getAuthorizations($idGroup){
        $sql = "
        SELECT Authorization.Id, Authorization.Name
		FROM \"Group\"  
		INNER JOIN GroupAuthorization ON \"Group\".Id = GroupAuthorization.IdGroup 
		INNER JOIN Authorization ON GroupAuthorization.IdAuthorization = Authorization.Id
		WHERE \"Group\".Id = :idGroup
        ORDER BY Authorization.Name";
    $query = $this->pdo->query($sql);
    $query->execute(['idGroup' => $idGroup ]);
    return $query->fetchAll(PDO::FETCH_ASSOC);
    }

}

?>