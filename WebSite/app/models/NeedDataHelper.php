<?php

declare(strict_types=1);

namespace app\models;

use RuntimeException;
use stdClass;
use app\helpers\Application;
use app\modules\Event\valueObjects\NeedWithTypeRow;

final class NeedDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /** @return list<NeedWithTypeRow> */
    public function getNeedsAndTheirTypes(): array
    {
        $sql = "
            SELECT Need.*, NeedType.Name AS TypeName
            FROM Need
            LEFT JOIN NeedType ON Need.IdNeedType = NeedType.Id
            ORDER BY NeedType.Name, Need.Name
        ";

        $stmt = $this->pdo->query($sql);

        if ($stmt === false) {
            throw new RuntimeException('Unable to execute query.');
        }

        return array_map(
            static fn(object $row): NeedWithTypeRow => NeedWithTypeRow::fromStdClass($row),
            $this->fetchObjects($stmt)
        );
    }

    /**
     * @return stdClass[]
     */
    public function needsforNeedType(int $needTypeId): array
    {
        $sql = "
            SELECT Need.*, NeedType.Name AS TypeName
            FROM Need
            JOIN NeedType ON Need.IdNeedType = NeedType.Id
            WHERE Need.IdNeedType = :needTypeId
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':needTypeId' => $needTypeId]);

        return $this->fetchObjects($stmt);
    }

    public function countForNeedType(int $needTypeid): int
    {
        return count($this->gets('Need', ['IdNeedType' => $needTypeid], '*'));
    }
}
