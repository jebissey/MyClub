<?php

declare(strict_types=1);

namespace app\models;

use DateTime;
use InvalidArgumentException;
use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;

class LogDataCompactHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function compactLog(int $removeOlderThanXmonths, int $compactOlderThanXmonths): void
    {
        $metadata = $this->get('Metadata', ['Id' => 1], 'Compact_lastDate, Compact_everyXdays, Compact_maxRecords');
        $maxRecords = isset($metadata->Compact_maxRecords) ? (int)$metadata->Compact_maxRecords : 0;
        if (!empty($metadata->Compact_everyXdays) && !empty($metadata->Compact_lastDate)) {
            $last = new DateTime($metadata->Compact_lastDate);
            $now  = new DateTime();
            $daysSinceLast = (int)$last->diff($now)->format('%a');
            $countBefore = (int)$this->pdoForLog->query("SELECT COUNT(*) FROM Log")->fetchColumn();
            if ($daysSinceLast >= (int)$metadata->Compact_everyXdays) {
                $this->compactRows($removeOlderThanXmonths, $compactOlderThanXmonths);
                $this->set('Metadata', ['Compact_lastDate' => (new DateTime())->format('Y-m-d H:i:s')], ['Id' => 1]);
            } else {
                if (random_int(1, 1000) === 1) {
                    $this->enforceMaxRecordsAndLog($maxRecords, $countBefore);
                }
            }
        }
    }

    #region Private functions
    private function compactRows(int $removeOlderThanXmonths, int $compactOlderThanXmonths): void
    {
        if ($removeOlderThanXmonths <= $compactOlderThanXmonths) {
            throw new InvalidArgumentException(
                "removeOlderThanXmonths ($removeOlderThanXmonths) must be strictly greater than compactOlderThanXmonths ($compactOlderThanXmonths)"
            );
        }

        $this->pdoForLog->beginTransaction();

        try {
            $emptyCondition = "IpAddress = '' AND Referer = '' AND Os = '' AND Browser = '' 
                          AND ScreenResolution = '' AND Type = '' AND Token = '' 
                          AND Code = '' AND Message = ''";

            $stmtDelete = $this->pdoForLog->prepare("
                DELETE FROM Log 
                WHERE CreatedAt < datetime('now', ?)
            ");
            $stmtDelete->execute(["-{$removeOlderThanXmonths} months"]);
            $deletedRows = $stmtDelete->rowCount();

            $stmtCompact = $this->pdoForLog->prepare("
                INSERT INTO Log (
                    IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, 
                    Token, Who, CreatedAt, Code, Message, Count
                )
                SELECT 
                    '', '', '', '', '', '',
                    Uri,
                    '',
                    Who,
                    datetime(CreatedAt, 'start of month') AS CompactedDate,
                    '', '',
                    SUM(Count) AS TotalCount
                FROM Log
                WHERE CreatedAt < datetime('now', ?)
                AND NOT ($emptyCondition)
                GROUP BY Uri, Who, strftime('%Y-%m', CreatedAt)
                HAVING SUM(Count) > 0;
            ");
            $stmtCompact->execute(["-{$compactOlderThanXmonths} months"]);
            $compactedInserted = $stmtCompact->rowCount();

            $stmtDeleteOld = $this->pdoForLog->prepare("
                DELETE FROM Log 
                WHERE CreatedAt < datetime('now', ?)
                AND NOT ($emptyCondition)
            ");
            $stmtDeleteOld->execute(["-{$compactOlderThanXmonths} months"]);
            $compactedDeleted = $stmtDeleteOld->rowCount();

            $this->pdoForLog->commit();
            $this->pdoForLog->exec("VACUUM");

            (new LogDataWriterHelper($this->application))->add((string)ApplicationError::Ok->value, "Compact log: {$deletedRows} old deleted, {$compactedInserted} compacted, {$compactedDeleted} compacted deleted");
        } catch (Throwable $e) {
            if ($this->pdoForLog->inTransaction()) {
                $this->pdoForLog->rollBack();
            }

            (new LogDataWriterHelper($this->application))->add(
                (string)ApplicationError::Error->value,
                "Compact log FAILED: " . $e->getMessage()
            );
        }
    }

    private function enforceMaxRecordsAndLog(int $maxRecords, int $countBefore): void
    {
        if ($maxRecords <= 0) return;
        $count = (int)$this->pdoForLog->query("SELECT COUNT(*) FROM Log")->fetchColumn();
        if ($count > $maxRecords) {
            $toDelete = $count - $maxRecords;
            $this->pdoForLog->exec("
                DELETE FROM Log
                WHERE Id IN (
                    SELECT Id FROM Log
                    ORDER BY datetime(CreatedAt) ASC
                    LIMIT $toDelete
                )
            ");
        }
        $countAfter = (int)$this->pdoForLog->query("SELECT COUNT(*) FROM Log")->fetchColumn();
        if ($countAfter != $countBefore) {
            (new LogDataWriterHelper($this->application))->add((string)ApplicationError::Ok->value, "Compact log from $countBefore to $countAfter");
        }
    }
}
