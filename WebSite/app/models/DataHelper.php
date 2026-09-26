<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\helpers\interfaces\ErrorManagerInterface;

final class DataHelper extends Data
{
    public function __construct(
        PDO $pdo,
        ErrorManagerInterface $errorManager,
        ?PDO $pdoForLog = null,
    ) {
        parent::__construct($pdo, $errorManager, $pdoForLog);
    }
}
