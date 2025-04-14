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

    public function searchAthleteRank($firstName, $lastName)
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
            return $this->parseAthleteRank($html, $firstName, $lastName);
        } catch (GuzzleException $e) {
            return ['error' => 'Erreur lors de la récupération des données: ' . $e->getMessage()];
        }
    }

    public function searchAthleteResults($firstName, $lastName, $year, $club)
    {
        $params = [
            'frmpostback' => 'true',
            'frmbase' => 'resultats',
            'frmmode' => '1',
            'frmespace' => '0',
            'frmsaison' => $year,
            'frmclub' => $club,
            'frmnom' => $lastName,
            'frmprenom' => $firstName,
        ];

        try {
            $response = $this->client->get($this->baseUrl, [
                'query' => $params,
                'allow_redirects' => true
            ]);
            $html = (string) $response->getBody();
            return $this->parseAthleteResults($html, $firstName, $lastName);
        } catch (GuzzleException $e) {
            return ['error' => 'Erreur lors de la récupération des données: ' . $e->getMessage()];
        }
    }
    private function parseAthleteRank($html, $firstName, $lastName)
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

    private function parseAthleteResults($html, $firstName, $lastName)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $searchName = strtoupper($lastName);
        $frmcompetition = $this->extractFrm($html, 'frmcompetition');
        $results = [];
        $rows = $xpath->query("//table[@id='ctnResultats']//tr[td[contains(@class, 'datas0') or contains(@class, 'datas1')]]");
        foreach ($rows as $row) {
            $rowText = $row->textContent;
            if (strpos($rowText, $searchName) !== false) {
                $isDatas0 = ($xpath->evaluate("count(.//td[@class='datas0'])", $row) > 0);
                $cellClass = $isDatas0 ? 'datas0' : 'datas1';
                $cells = $xpath->query(".//td[@class='$cellClass']", $row);
                if ($cells->length >= 10) {
                    $code = trim($cells->item(7)->textContent);
                    $results[] = [
                        'date' => trim($cells->item(0)->textContent),
                        'competition' => trim($cells->item(2)->textContent),
                        'place' => trim($cells->item(4)->textContent),
                        'time' => trim(strip_tags($cells->item(5)->textContent)), // Retire les balises <b>
                        'category' => $code,
                        'round' => trim($cells->item(8)->textContent),
                        'location' => trim($cells->item(9)->textContent),
                        'url' => "https://bases.athle.fr/asp.net/liste.aspx?frmbase=resultats&frmmode=1&frmespace=0&frmcompetition=" . $frmcompetition . "&frmcategorie=" . substr($code, 0, 2) . "&frmsexe=" . substr($code, 2, 1),
                    ];
                }
            }
        }

        return $results;
    }

    private function extractFrm($html, $var)
    {
        $pattern = "/" . $var . "=(\d+)/";

        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
