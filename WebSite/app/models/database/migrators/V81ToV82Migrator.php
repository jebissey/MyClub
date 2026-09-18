<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

class V81ToV82Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<SQL
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('availability.stats.title',
'Availability statistics',
'Statistiques de disponibilité',
'Statystyki dostępności'),
('availability.filled.title',
'Response rate',
'Taux de réponses',
'Wskaźnik odpowiedzi'),
('availability.filled.members',
'members have submitted their availability',
'membres ont renseigné leurs disponibilités',
'członków podało swoją dostępność'),
('availability.by_slot.title',
'Breakdown by time slot',
'Répartition par créneau',
'Podział według przedziałów czasowych'),
('availability.morning',
'Morning',
'Matin',
'Rano'),
('availability.afternoon',
'Afternoon',
'Après-midi',
'Popołudnie'),
('availability.evening',
'Evening',
'Soir',
'Wieczór'),
('availability.percent_available',
'% available',
'% disponible',
'% dostępnych'),
('navbar.event_manager.availabilities',
'Availabilities',
'Disponibilités',
'Dostępności'),
('day.monday',
'Monday',
'Lundi',
'Poniedziałek'),
('day.tuesday',
'Tuesday',
'Mardi',
'Wtorek'),
('day.wednesday',
'Wednesday',
'Mercredi',
'Środa'),
('day.thursday',
'Thursday',
'Jeudi',
'Czwartek'),
('day.friday',
'Friday',
'Vendredi',
'Piątek'),
('day.saturday',
'Saturday',
'Samedi',
'Sobota'),
('day.sunday',
'Sunday',
'Dimanche',
'Niedziela');
SQL);

        return 82;
    }
}
