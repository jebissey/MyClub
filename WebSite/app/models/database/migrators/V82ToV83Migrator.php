<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

class V82ToV83Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
        INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
        ('participation.title',
        'Actual participation by time slot',
        'Participation effective par créneau',
        'Rzeczywista frekwencja według przedziału czasowego'),
        ('participation.startDate',
        'Start date',
        'Date de début',
        'Data początkowa'),
        ('participation.range',
        'Period',
        'Période',
        'Okres'),
        ('participation.apply',
        'Apply',
        'Appliquer',
        'Zastosuj'),
        ('participation.average',
        'Average',
        'Moyenne',
        'Średnia'),
        ('participation.total',
        'Total',
        'Total',
        'Suma'),
        ('participation.events',
        'Events',
        'Événements',
        'Wydarzenia'),
        ('range.1week',
        '1 week',
        '1 semaine',
        '1 tydzień'),
        ('range.5weeks',
        '5 weeks',
        '5 semaines',
        '5 tygodni'),
        ('range.3months',
        '3 months',
        '3 mois',
        '3 miesiące'),
        ('range.6months',
        '6 months',
        '6 mois',
        '6 miesięcy'),
        ('range.9months',
        '9 months',
        '9 mois',
        '9 miesięcy'),
        ('range.1year',
        '1 year',
        '1 an',
        '1 rok');
        SQL);

        return 83;
    }
}
