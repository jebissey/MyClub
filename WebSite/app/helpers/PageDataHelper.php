<?php

namespace app\helpers;

class PageDataHelper extends Data
{
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
        return $this->fluent
            ->from('Page')
            ->leftJoin("'Group' ON Page.IdGroup = 'Group'.Id")
            ->select("'Group'.Name AS GroupName")
            ->orderBy('Position')
            ->fetchAll();
    }

    public function insertOrUpdate($data)
    {
        if (empty($data['id'])) {
            $maxPosition = $this->fluent->from('Page')->select('MAX(Position) AS MaxPos')->fetch();
            $newPosition = ($maxPosition && $maxPosition->MaxPos) ? $maxPosition->MaxPos + 1 : 1;
            $this->fluent->insertInto('Page')
                ->values([
                    'Name' => $data['name'],
                    'Route' => $data['route'],
                    'Position' => $newPosition,
                    'IdGroup' => $data['idGroup'],
                    'ForMembers' => $data['forMembers'],
                    'ForAnonymous' => $data['forAnonymous'],
                ])
                ->execute();
        } else {
            $this->fluent->update('Page')
                ->set([
                    'Name' => $data['name'],
                    'Route' => $data['route'],
                    'IdGroup' => $data['idGroup'],
                    'ForMembers' => $data['forMembers'],
                    'ForAnonymous' => $data['forAnonymous'],
                ])
                ->where('Id', $data['id'])
                ->execute();
        }
    }

    public function updates($positions)
    {
        foreach ($positions as $id => $position) {
            $this->fluent->update('Page')
                ->set(['Position' => $position])
                ->where('Id', $id)
                ->execute();
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
            if (!$person && $pageData->ForAnonymous) return true;
            if ($person && $pageData->ForMembers)    return true;
            return false;
        }
        if (!$person) return false;
        $userGroups = $this->application->getAuthorizations()->getUserGroups($person->Email);
        return in_array($pageData->IdGroup, $userGroups);
    }
}
