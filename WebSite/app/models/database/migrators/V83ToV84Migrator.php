<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

class V83ToV84Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $pdo->exec(<<<'SQL'
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES

('astronomy.title',
    'Astronomy',
    'Astronomie',
    'Astronomia'),

('astronomy.location',
    'Location',
    'Localisation',
    'Lokalizacja'),

('astronomy.name',
    'Name',
    'Nom',
    'Nazwa'),

('astronomy.city_placeholder',
    'City…',
    'Ville…',
    'Miasto…'),

('astronomy.latitude',
    'Latitude',
    'Latitude',
    'Szerokość geograficzna'),

('astronomy.longitude',
    'Longitude',
    'Longitude',
    'Długość geograficzna'),

('astronomy.save',
    'Save',
    'Enregistrer',
    'Zapisz'),

('astronomy.use_geolocation',
    'Use my location',
    'Utiliser ma position',
    'Użyj mojej lokalizacji'),

('astronomy.sunrise',
    'Sunrise',
    'Lever du soleil',
    'Wschód słońca'),

('astronomy.sunset',
    'Sunset',
    'Coucher du soleil',
    'Zachód słońca'),

('astronomy.moonrise',
    'Moonrise',
    'Lever de la Lune',
    'Wschód Księżyca'),

('astronomy.moonset',
    'Moonset',
    'Coucher de la Lune',
    'Zachód Księżyca'),

('astronomy.planet',
    'Planet',
    'Planète',
    'Planeta'),

('astronomy.body',
    'Body',
    'Corps',
    'Ciało niebieskie'),

('astronomy.sun',
    'Sun',
    'Soleil',
    'Słońce'),

('astronomy.moon',
    'Moon',
    'Lune',
    'Księżyc'),

('astronomy.mercury',
    'Mercury',
    'Mercure',
    'Merkury'),

('astronomy.venus',
    'Venus',
    'Vénus',
    'Wenus'),

('astronomy.mars',
    'Mars',
    'Mars',
    'Mars'),

('astronomy.jupiter',
    'Jupiter',
    'Jupiter',
    'Jowisz'),

('astronomy.saturn',
    'Saturn',
    'Saturne',
    'Saturn'),

('astronomy.rise',
    'Rise',
    'Lever',
    'Wschód'),

('astronomy.set',
    'Set',
    'Coucher',
    'Zachód'),

('astronomy.visible',
    'Visible',
    'Visible',
    'Widoczny'),

('astronomy.altitude',
    'Altitude',
    'Altitude',
    'Wysokość'),

('astronomy.azimuth',
    'Azimuth',
    'Azimut',
    'Azymut'),

('astronomy.time',
    'Time',
    'Heure',
    'Czas'),

('astronomy.south',
    'South',
    'Sud',
    'Południe'),

('astronomy.east',
    'East',
    'Est',
    'Wschód'),

('astronomy.west',
    'West',
    'Ouest',
    'Zachód'),

('astronomy.horizon',
    'Horizon',
    'Horizon',
    'Horyzont'),

('astronomy.naked_eye',
    'Visible to the naked eye',
    'Visible à l’œil nu',
    'Widoczne gołym okiem'),

('astronomy.magnitude',
    'Magnitude',
    'Magnitude',
    'Jasność'),

('astronomy.illumination',
    'Illumination',
    'Illumination',
    'Oświetlenie'),

('astronomy.rise_set_title',
    'Rise & set (current day)',
    'Lever & coucher (jour courant)',
    'Wschód i zachód (bieżący dzień)'),

('astronomy.sky_position',
    'Position in the sky (looking south)',
    'Position dans le ciel (regard vers le Sud)',
    'Pozycja na niebie (patrząc na południe)'),

('astronomy.azimuth_description',
    'Azimuth is measured from South (0°) towards West (positive) / East (negative). 
    The ends extend beyond ±90° to allow rises/sets to be displayed in summer.',
    'L’azimut est mesuré depuis le Sud (0°) vers l’Ouest (positif) / Est (négatif). 
    Les extrémités dépassent ±90° pour permettre d’afficher les levers/couchers en été.',
    'Azymut jest mierzony od południa (0°) w kierunku zachodnim (wartość dodatnia) / wschodnim (wartość ujemna). 
    Końce przekraczają ±90°, aby umożliwić wyświetlanie wschodów i zachodów latem.'),

('astronomy.location_saved',
    'Location saved.',
    'Localisation enregistrée.',
    'Lokalizacja została zapisana.'),

('astronomy.geolocation_not_supported',
    'Geolocation is not supported.',
    'La géolocalisation n’est pas prise en charge.',
    'Geolokalizacja nie jest obsługiwana.'),

('astronomy.searching_position',
    'Searching for your position…',
    'Recherche de la position…',
    'Wyszukiwanie pozycji…'),

('astronomy.position_saved',
    'GPS position saved.',
    'Position GPS enregistrée.',
    'Pozycja GPS została zapisana.'),

('astronomy.position_unavailable',
    'Unable to get your position.',
    'Impossible d’obtenir la position.',
    'Nie można uzyskać pozycji.'),

('astronomy.my_position',
    'My position',
    'Ma position',
    'Moja pozycja'),

('astronomy.invalid_coordinates',
    'Invalid coordinates',
    'Coordonnées invalides',
    'Nieprawidłowe współrzędne'),

('astronomy.cookie_save_failed',
    'Unable to save location cookie',
    'Impossible d’enregistrer le cookie de localisation',
    'Nie można zapisać ciasteczka lokalizacji');

SQL);

        return 84;
    }
}
