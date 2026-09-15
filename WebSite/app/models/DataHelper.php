<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\helpers\ErrorManager;

final class DataHelper extends Data
{
    public function __construct(
        PDO $pdo,
        ErrorManager $errorManager,
        ?PDO $pdoForLog = null,
    ) {
        parent::__construct($pdo, $errorManager, $pdoForLog);
    }
}
