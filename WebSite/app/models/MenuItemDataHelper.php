<?php

declare(strict_types=1);

namespace app\models;

use InvalidArgumentException;
use PDO;
use Throwable;
use app\helpers\Application;
use app\valueObjects\MaxPositionRow;
use app\valueObjects\MenuItemAuthorizationRow;
use app\valueObjects\Person;

class MenuItemDataHelper extends Data
{
    public function __construct(Application $application, private AuthorizationDataHelper $authorizationDataHelper)
    {
        parent::__construct($application);
    }

    public function authorizedUser(string $url, ?Person $person): bool
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
        /** @var object{IdGroup: int|string|null, ForMembers: int|string, ForAnonymous: int|string, groupId: int|string|null}|false $raw */
        $raw = $stmt->fetch(PDO::FETCH_OBJ);

        if ($raw === false) {
            return false;
        }
        $pageData = MenuItemAuthorizationRow::fromStdClass($raw);

        if (!$pageData->IdGroup) {
            if (
                ($person === null && $pageData->ForAnonymous) ||
                ($person !== null && $pageData->ForMembers)
            ) {
                return true;
            }
            return false;
        }
        if ($person === null) {
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

    /**
     * @param array{
     *     what: string,
     *     type: string,
     *     label?: string|null,
     *     icon?: string|null,
     *     url?: string|null,
     *     idGroup?: int|null,
     *     parentId?: int|null,
     *     forMembers?: bool,
     *     forContacts?: bool,
     *     forAnonymous?: bool,
     *     id?: int,
     *     position?: int
     * } $data
     */
    public function insertOrUpdate(array $data): void
    {
        if (!in_array($data['what'], $this->getCheckValues('MenuItem', 'What'))) {
            throw new InvalidArgumentException("Invalid 'What' value: {$data['what']}");
        }
        if (!in_array($data['type'], $this->getCheckValues('MenuItem', 'Type'))) {
            throw new InvalidArgumentException("Invalid 'Type' value: {$data['type']}");
        }
        if (!empty($data['parentId'])) {
            $parent = $this->get('MenuItem', ['Id' => $data['parentId']]);
            if ($parent === false) {
                throw new InvalidArgumentException("ParentId {$data['parentId']} does not exist.");
            }
            /** @var object{Position: int} $parent */
        }
        if (!empty($data['idGroup'])) {
            $group = $this->get('Group', ['Id' => $data['idGroup']]);
            if ($group === false) {
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
            if (isset($parent)) {
                /** @var object{MaxPos: int|string|null}|false $maxPosRaw */
                $maxPosRaw = $this->fluent
                    ->from('MenuItem')
                    ->where('ParentId = ?', $menuItem['ParentId'])
                    ->select('MAX(Position) AS MaxPos')
                    ->fetch();
                $maxPos = $maxPosRaw !== false ? MaxPositionRow::fromStdClass($maxPosRaw) : null;
                $menuItem['Position'] = max($parent->Position + 1, ($maxPos->MaxPos ?? 0) + 1);
            } else {
                /** @var object{MaxPos: int|string|null}|false $maxPosRaw */
                $maxPosRaw = $this->fluent->from('MenuItem')->select('MAX(Position) AS MaxPos')->fetch();
                $maxPos = $maxPosRaw !== false ? MaxPositionRow::fromStdClass($maxPosRaw) : null;
                $menuItem['Position'] = $maxPos?->MaxPos !== null ? $maxPos->MaxPos + 1 : 1;
            }

            $this->set('MenuItem', $menuItem);
        } else {
            if (!array_key_exists('position', $data)) {
                throw new InvalidArgumentException("Position is required when updating a menu item.");
            }
            if (!empty($menuItem['ParentId'])) {
                $parent = $this->get('MenuItem', ['Id' => $menuItem['ParentId']], 'Position');
                if ($parent === false) {
                    throw new InvalidArgumentException("ParentId {$menuItem['ParentId']} does not exist.");
                }
                /** @var object{Position: int} $parent */
                if ($data['position'] <= $parent->Position) {
                    throw new InvalidArgumentException("Child position must be greater than parent position.");
                }
            }
            $this->set('MenuItem', $menuItem, ['Id' => $data['id']]);
        }
    }

    /**
     * @param array<int, int> $positions
     */
    public function updates(array $positions): void
    {
        foreach ($positions as $id => $position) {
            $this->set('MenuItem', ['Position' => $position], ['Id' => $id]);
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
