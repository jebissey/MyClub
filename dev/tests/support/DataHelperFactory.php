<?php

declare(strict_types=1);

namespace tests\support;

use PDO;
use ReflectionClass;
use ReflectionProperty;
use app\models\Data;

/**
 * Builds a real, final DataHelper on top of a PDO, bypassing its constructor
 * (which needs a full Application).
 */
final class DataHelperFactory
{
    /**
     * @template T of Data
     * @param class-string<T> $class
     * @return T
     */
    public static function create(string $class, PDO $pdo): Data
    {
        $helper = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(Data::class, 'pdo'))->setValue($helper, $pdo);

        // Data's constructor normally loads the list of tables
        $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'");
        $tables = $statement === false ? [] : $statement->fetchAll(PDO::FETCH_COLUMN);
        (new ReflectionProperty(Data::class, 'tables'))->setValue($helper, $tables);

        return $helper;
    }
}
