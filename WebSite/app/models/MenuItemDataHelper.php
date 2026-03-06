<?php

declare(strict_types=1);

namespace app\models;

use InvalidArgumentException;
use PDO;
use Throwable;

use app\helpers\Application;

class MenuItemDataHelper extends Data
{
    public function __construct(Application $application, private AuthorizationDataHelper $authorizationDataHelper)
    {
        parent::__construct($application);
    }

    public function authorizedUser(string $url, object|false $person): bool
    {
        $sql = '
            SELECT 
                MenuItem.IdGroup,
                MenuItem.ForMembers,
                MenuItem.ForAnonymous,
                "Group".Id AS groupId
            FROM MenuItem
            LEFT JOIN "Group" ON MenuItem.IdGroup = "Group".Id
            WHERE MenuItem.Url = :route
            LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':route' => $url]);
        $pageData = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$pageData) {
            return false;
        }

        if (!$pageData->IdGroup) {
            if (
                (!$person && $pageData->ForAnonymous) ||
                ($person && $pageData->ForMembers)
            ) {
                return true;
            }
            return false;
        }
        if (!$person) {
            return false;
        }
        $userGroups = $this->authorizationDataHelper->getUserGroups($person->Email);
        return in_array($pageData->IdGroup, $userGroups);
    }

    public function del(int $id): int
    {
        $this->pdo->beginTransaction();

        try {
            $deleted = $this->deleteRecursive($id);

            $this->pdo->commit();
            return $deleted;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function gets_(): array
    {
        $sql = "
            SELECT 
                MenuItem.*,
                \"Group\".Name AS GroupName
            FROM MenuItem
            LEFT JOIN \"Group\" ON MenuItem.IdGroup = \"Group\".Id
            ORDER BY Position
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function insertOrUpdate(array $data): void
    {
        if (!in_array($data['what'], $this->getCheckValues('MenuItem', 'What'))) {
            throw new InvalidArgumentException("Invalid 'What' value: {$data['what']}");
        }
        if (!in_array($data['type'], $this->getCheckValues('MenuItem', 'Type'))) {
            throw new InvalidArgumentException("Invalid 'Type' value: {$data['type']}");
        }
        if (!empty($data['parentId'])) {
            $parent = $this->fluent->from('MenuItem')->where('Id = ?', $data['parentId'])->fetch();
            if (!$parent) {
                throw new InvalidArgumentException("ParentId {$data['parentId']} does not exist.");
            }
        }
        if (!empty($data['idGroup'])) {
            $group = $this->fluent->from('"Group"')->where('Id = ?', $data['idGroup'])->fetch();
            if (!$group) {
                throw new InvalidArgumentException("IdGroup {$data['idGroup']} does not exist.");
            }
        }
        $menuItem = [
            'What' => $data['what'],
            'Type' => $data['type'],
            'Label' => $data['label'] ?? null,
            'Icon' => $data['icon'] ?? null,
            'Url' => $data['url'] ?? null,
            'IdGroup' => $data['idGroup'] ?? null,
            'ParentId' => $data['parentId'] ?? null,
            'ForMembers' => !empty($data['forMembers']) ? 1 : 0,
            'ForContacts' => !empty($data['forContacts']) ? 1 : 0,
            'ForAnonymous' => !empty($data['forAnonymous']) ? 1 : 0,
        ];

        if (empty($data['id'])) {
            if (!empty($menuItem['ParentId'])) {
                $maxPos = $this->fluent
                    ->from('MenuItem')
                    ->where('ParentId = ?', $menuItem['ParentId'])
                    ->select('MAX(Position) AS MaxPos')
                    ->fetch();
                $menuItem['Position'] = max($parent->Position + 1, ($maxPos->MaxPos ?? 0) + 1);
            } else {
                $maxPos = $this->fluent->from('MenuItem')->select('MAX(Position) AS MaxPos')->fetch();
                $menuItem['Position'] = ($maxPos && $maxPos->MaxPos) ? $maxPos->MaxPos + 1 : 1;
            }

            $this->set('MenuItem', $menuItem);
        } else {
            if (!empty($menuItem['ParentId'])) {
                $parent = $this->fluent->from('MenuItem')->where('Id = ?', $menuItem['ParentId'])->fetch();
                if ($data['position'] <= $parent->Position) {
                    throw new InvalidArgumentException("Child position must be greater than parent position.");
                }
            }
            $this->set('MenuItem', $menuItem, ['Id' => $data['id']]);
        }
    }

    public function updates(array $positions): void
    {
        foreach ($positions as $id => $position) {
            $this->set('SidebarItem', ['Position' => $position], ['Id' => $id]);
        }
    }

    #region Private functions
    private function deleteRecursive(int $id): int
    {
        $count = 0;

        $stmt = $this->pdo->prepare("SELECT Id FROM MenuItem WHERE ParentId = :parentId");
        $stmt->execute(['parentId' => $id]);
        $children = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($children as $childId) {
            $count += $this->deleteRecursive((int)$childId);
        }

        $stmt = $this->pdo->prepare("DELETE FROM MenuItem WHERE Id = :id");
        $stmt->execute(['id' => $id]);

        return $count + $stmt->rowCount();
    }
}
