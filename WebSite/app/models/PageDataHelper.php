<?php

declare(strict_types=1);

namespace app\models;

use PDO;

use app\helpers\Application;

class PageDataHelper extends Data
{
    public function __construct(Application $application, private AuthorizationDataHelper $authorizationDataHelper)
    {
        parent::__construct($application);
    }

    public function del($id)
    {
        return $this->fluent->deleteFrom('Page')->where('Id', $id)->execute();
    }

    public function get_($id): mixed
    {
        return $this->fluent->from('Page')->where('Id', $id)->fetch();
    }

    public function gets_(): array
    {
        $sql = "
            SELECT 
                Page.*,
                \"Group\".Name AS GroupName
            FROM Page
            LEFT JOIN \"Group\" ON Page.IdGroup = \"Group\".Id
            ORDER BY Position
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function insertOrUpdate($data)
    {
        if (empty($data['id'])) {
            $maxPosition = $this->fluent->from('Page')->select('MAX(Position) AS MaxPos')->fetch();
            $newPosition = ($maxPosition && $maxPosition->MaxPos) ? $maxPosition->MaxPos + 1 : 1;
            $this->set('Page', [
                'Name' => $data['name'],
                'Route' => $data['route'],
                'Position' => $newPosition,
                'IdGroup' => $data['idGroup'],
                'ForMembers' => $data['forMembers'],
                'ForAnonymous' => $data['forAnonymous']
            ]);
        } else $this->set('Page', [
            'Name' => $data['name'],
            'Route' => $data['route'],
            'IdGroup' => $data['idGroup'],
            'ForMembers' => $data['forMembers'],
            'ForAnonymous' => $data['forAnonymous'],
        ], ['Id' => $data['id']]);
    }

    public function updates($positions)
    {
        foreach ($positions as $id => $position) {
            $this->set('Page', ['Position' => $position], ['Id' => $id]);
        }
    }

    public function authorizedUser($page, $person): bool
    {
        $pageData = $this->fluent->from('Page')
            ->select('"Page".IdGroup, "Page".ForMembers, "Page".ForAnonymous, "Group".Id AS groupId')
            ->leftJoin('"Group" ON Page.IdGroup = "Group".Id')
            ->where('Page.Route', $page)
            ->fetch();
        if (!$pageData) return false;
        if (!$pageData->IdGroup) {
            if ((!$person && $pageData->ForAnonymous) || ($person && $pageData->ForMembers)) return true;
            return false;
        }
        if (!$person) return false;
        $userGroups = $this->authorizationDataHelper->getUserGroups($person->Email);
        return in_array($pageData->IdGroup, $userGroups);
    }
}
