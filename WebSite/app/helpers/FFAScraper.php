<?php

namespace app\helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use DOMDocument;
use DOMXPath;

class FFAScraper
{
    private $baseUrl = 'https://bases.athle.fr/asp.net/liste.aspx';
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3'
            ]
        ]);
    }

    public function searchAthlete($firstName, $lastName)
    {
        $params = [
            'frmpostback' => 'true',
            'frmbase' => 'coupe',
            'frmmode' => '1',
            'frmespace' => '0',
            'frmsaison' => '2025',
            'frmepreuve' => 'Classement du Circuit Marche Nordique',
            'frmsexe' => '',
            'frmnom' => $lastName,
            'frmlicence' => '',
            'frmligue' => '',
            'frmdepartement' => '',
            'frmposition' => '2'
        ];

        try {
            $response = $this->client->get($this->baseUrl, [
                'query' => $params,
                'allow_redirects' => true
            ]);
            $html = (string) $response->getBody();
            return $this->parseAthleteData($html, $firstName, $lastName);
        } catch (GuzzleException $e) {
            return ['error' => 'Erreur lors de la récupération des données: ' . $e->getMessage()];
        }
    }

    private function parseAthleteData($html, $firstName, $lastName)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $fullName = strtoupper($lastName . ' ' . $firstName);
        $rows = $xpath->query("//table[@id='ctnCoupe']//tr[td[@class='datas0']]");

        foreach ($rows as $row) {
            $rowText = $row->textContent;

            if (strpos($rowText, $fullName) !== false) {
                $dataCells = $xpath->query(".//td[@class='datas0']", $row);
                if ($dataCells->length >= 5) {
                    return [
                        'rank' => trim($dataCells->item(0)->textContent),
                        'event' => trim($dataCells->item(1)->textContent),
                        'name' => trim($dataCells->item(2)->textContent),
                        'club' => trim($dataCells->item(3)->textContent),
                        'points' => trim($dataCells->item(4)->textContent)
                    ];
                }
            }
        }
        return null;
    }
}
