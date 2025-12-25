<?php

declare(strict_types=1);

namespace app\models;

use DateTime;
use PDO;
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
        $countBefore = (int)$this->pdoForLog->query("SELECT COUNT(*) FROM Log")->fetchColumn();
        $metadata = $this->get('Metadata', ['Id' => 1], 'Compact_lastDate, Compact_everyXdays, Compact_maxRecords');
        $maxRecords = isset($metadata->Compact_maxRecords) ? (int)$metadata->Compact_maxRecords : 0;
        if (!empty($metadata->Compact_everyXdays) && !empty($metadata->Compact_lastDate)) {
            $last = new DateTime($metadata->Compact_lastDate);
            $now  = new DateTime();
            $daysSinceLast = (int)$last->diff($now)->format('%a');
            if ($daysSinceLast < (int)$metadata->Compact_everyXdays) {
                $this->enforceMaxRecordsAndLog($maxRecords, $countBefore);
                return;
            }
        }
        $removeParam  = "-{$removeOlderThanXmonths} months";
        $compactParam = "-{$compactOlderThanXmonths} months";
        try {
            $this->pdoForLog->beginTransaction();

            $stmtDeleteOld = $this->pdoForLog->prepare("
                DELETE FROM Log
                WHERE CreatedAt < datetime('now', ?)
            ");
            $stmtDeleteOld->execute([$removeParam]);

            $compactedRows = $this->pdoForLog->query("
                SELECT 
                    '' AS IpAddress,
                    '' AS Referer,
                    '' AS Os,
                    '' AS Browser,
                    '' AS ScreenResolution,
                    '' AS Type,
                    Uri,
                    '' AS Token,
                    Who,
                    datetime(CreatedAt, 'start of month') AS CreatedAt,
                    '' AS Code,
                    '' AS Message,
                    COUNT(*) AS Count
                FROM Log
                WHERE CreatedAt < datetime('now', '{$compactParam}')
                GROUP BY Uri, Who, strftime('%Y-%m', CreatedAt)
            ")->fetchAll(PDO::FETCH_ASSOC);
            if ($compactedRows === []) {
                $this->set('Metadata', ['Compact_lastDate' => (new DateTime())->format('Y-m-d H:i:s')], ['Id' => 1]);
                $this->pdoForLog->commit();
                $this->enforceMaxRecordsAndLog($maxRecords, $countBefore);
                return;
            }

            $stmtDeleteCompact = $this->pdoForLog->prepare("
                DELETE FROM Log
                WHERE CreatedAt < datetime('now', ?)
            ");
            $stmtDeleteCompact->execute([$compactParam]);

            $insert = $this->pdoForLog->prepare("
                INSERT INTO Log (IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, Token, Who, CreatedAt, Code, Message, Count)
                VALUES (:IpAddress, :Referer, :Os, :Browser, :ScreenResolution, :Type, :Uri, :Token, :Who, :CreatedAt, :Code, :Message, :Count)
            ");
            foreach ($compactedRows as $row) {
                $insert->execute($row);
            }
            $this->set('Metadata', ['Compact_lastDate' => (new DateTime())->format('Y-m-d H:i:s')], ['Id' => 1]);
            $this->pdoForLog->commit();
        } catch (Throwable $e) {

            if ($this->pdoForLog->inTransaction()) $this->pdoForLog->rollBack();
            (new LogDataWriterHelper($this->application))->add((string)ApplicationError::Error->value, "Compact log FAILED: " . $e->getMessage());
            return;
        }
        $this->enforceMaxRecordsAndLog($maxRecords, $countBefore);
    }

    #region Private functions
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
