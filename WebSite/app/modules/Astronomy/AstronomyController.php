<?php

declare(strict_types=1);

namespace app\modules\Astronomy;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\To;
use app\helpers\WebApp;
use app\modules\Astronomy\viewModels\AstronomyViewModel;
use app\modules\Common\AbstractController;

final class AstronomyController extends AbstractController
{
    private const COOKIE_NAME = 'astro_location';
    private const COOKIE_TTL  = 60 * 60 * 24 * 365; // 1 year

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function show(): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $saved = filter_input(INPUT_GET, 'saved', FILTER_VALIDATE_INT);
        $locationSaved = $saved === false || $saved === null ? null : ($saved === 1);

        $dateParam = filter_input(INPUT_GET, 'date', FILTER_UNSAFE_RAW);
        $currentDate = $this->parseDateOrToday(is_string($dateParam) ? $dateParam : null);

        $location = $this->getLocationFromCookieOrDefault();
        $i18n = [
            'title' => ($this->t)('astronomy.title'),

            'location' => ($this->t)('astronomy.location'),
            'name' => ($this->t)('astronomy.name'),
            'cityPlaceholder' => ($this->t)('astronomy.city_placeholder'),
            'latitude' => ($this->t)('astronomy.latitude'),
            'longitude' => ($this->t)('astronomy.longitude'),
            'save' => ($this->t)('astronomy.save'),
            'useGeolocation' => ($this->t)('astronomy.use_geolocation'),

            'sunrise' => ($this->t)('astronomy.sunrise'),
            'sunset' => ($this->t)('astronomy.sunset'),
            'moonrise' => ($this->t)('astronomy.moonrise'),
            'moonset' => ($this->t)('astronomy.moonset'),

            'planet' => ($this->t)('astronomy.planet'),
            'body' => ($this->t)('astronomy.body'),
            'sun' => ($this->t)('astronomy.sun'),
            'moon' => ($this->t)('astronomy.moon'),
            'mercury' => ($this->t)('astronomy.mercury'),
            'venus' => ($this->t)('astronomy.venus'),
            'mars' => ($this->t)('astronomy.mars'),
            'jupiter' => ($this->t)('astronomy.jupiter'),
            'saturn' => ($this->t)('astronomy.saturn'),

            'rise' => ($this->t)('astronomy.rise'),
            'set' => ($this->t)('astronomy.set'),
            'visible' => ($this->t)('astronomy.visible'),

            'altitude' => ($this->t)('astronomy.altitude'),
            'azimuth' => ($this->t)('astronomy.azimuth'),
            'time' => ($this->t)('astronomy.time'),
            'south' => ($this->t)('astronomy.south'),
            'east' => ($this->t)('astronomy.east'),
            'west' => ($this->t)('astronomy.west'),
            'horizon' => ($this->t)('astronomy.horizon'),

            'nakedEye' => ($this->t)('astronomy.naked_eye'),
            'magnitude' => ($this->t)('astronomy.magnitude'),
            'illumination' => ($this->t)('astronomy.illumination'),

            'riseSetTitle' => ($this->t)('astronomy.rise_set_title'),
            'skyPosition' => ($this->t)('astronomy.sky_position'),
            'azimuthDescription' => ($this->t)('astronomy.azimuth_description'),

            'locationSaved' => ($this->t)('astronomy.location_saved'),
            'geolocationNotSupported' => ($this->t)('astronomy.geolocation_not_supported'),
            'searchingPosition' => ($this->t)('astronomy.searching_position'),
            'positionSaved' => ($this->t)('astronomy.position_saved'),
            'positionUnavailable' => ($this->t)('astronomy.position_unavailable'),
            'myPosition' => ($this->t)('astronomy.my_position'),
            'invalidCoordinates' => ($this->t)('astronomy.invalid_coordinates'),
            'cookieSaveFailed' => ($this->t)('astronomy.cookie_save_failed'),
        ];
        $viewModel = new AstronomyViewModel(
            latitude: $location['lat'],
            longitude: $location['lng'],
            locationName: $location['name'],
            locationSaved: $locationSaved,
            currentDate: $currentDate->format('Y-m-d'),
            navItems: $this->getNavItems($this->application->getConnectedUser()->person),
            i18n: $i18n,
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
            layout: $this->getLayout(),
        );

        $this->render('Astronomy/views/astronomy.latte', $viewModel->toArray());
    }

    private function parseDateOrToday(?string $date): \DateTimeImmutable
    {
        if ($date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
            if ($parsed !== false) {
                return $parsed->setTime(0, 0);
            }
        }
        return new \DateTimeImmutable('today');
    }

    public function saveLocation(): void
    {
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $schema = [
            'lat'  => FilterInputRule::Float->value,
            'lng'  => FilterInputRule::Float->value,
            'name' => FilterInputRule::HtmlSafeName->value,
        ];
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->data->getData());

        $lat = $filterValues['lat'] ?? null;
        $lng = $filterValues['lng'] ?? null;

        $valid = is_numeric($lat) && is_numeric($lng)
            && (float) $lat >= -90 && (float) $lat <= 90
            && (float) $lng >= -180 && (float) $lng <= 180;

        if ($valid) {
            $payload = json_encode([
                'lat'  => round((float) $lat, 5),
                'lng'  => round((float) $lng, 5),
                'name' => mb_substr(To::str($filterValues['name'] ?? '', ''), 0, 100),
            ]);

            if ($payload !== false) {
                setcookie(
                    self::COOKIE_NAME,
                    $payload,
                    [
                        'expires'  => time() + self::COOKIE_TTL,
                        'path'     => '/',
                        'secure'   => true,
                        'httponly' => false, // accessible en JS pour la géolocalisation
                        'samesite' => 'Lax',
                    ]
                );
            }
        }

        $this->redirect('/astronomy?saved=' . ($valid ? '1' : '0'));
    }

    /**
     * @return array{lat: float, lng: float, name: string}
     */
    private function getLocationFromCookieOrDefault(): array
    {
        $default = [
            'lat'  => 48.8566,
            'lng'  => 2.3522,
            'name' => 'Paris',
        ];

        if (empty($_COOKIE[self::COOKIE_NAME])) {
            return $default;
        }

        $data = json_decode(To::str($_COOKIE[self::COOKIE_NAME], ''), true);
        if (!is_array($data) || !isset($data['lat'], $data['lng'])) {
            return $default;
        }

        return [
            'lat'  => (float) $data['lat'],
            'lng'  => (float) $data['lng'],
            'name' => To::str($data['name'] ?? '', ''),
        ];
    }
}
