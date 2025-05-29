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
            'timeout' => 5,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3'
            ]
        ]);
    }

    public function searchAthleteRank($firstName, $lastName, $year, $club)
    {
        $params = [
            'frmpostback' => 'true',
            'frmbase' => 'coupe',
            'frmmode' => '1',
            'frmespace' => '0',
            'frmsaison' => $year,
            'frmepreuve' => 'Classement du Circuit Marche Nordique',
            'frmsexe' => '',
            'frmnom' => $lastName,
            'frmprenom' => $firstName,
            'frmclub' => $club,
            'frmposition' => '2'
        ];

        try {
            $response = $this->client->get($this->baseUrl, [
                'query' => $params,
                'allow_redirects' => true
            ]);
            $html = (string) $response->getBody();
            return $this->parseAthleteRank($html);
        } catch (GuzzleException $e) {
            return ['error' => 'Erreur lors de la récupération des données : ' . $e->getMessage()];
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
            return $this->parseAthleteResults($html);
        } catch (GuzzleException $e) {
            return ['error' => 'Erreur lors de la récupération des données: ' . $e->getMessage()];
        }
    }
    private function parseAthleteRank($html)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query("//table[@id='ctnCoupe']//tr[td[@class='datas0']]");

        foreach ($rows as $row) {
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
        return null;
    }

    private function parseAthleteResults($html)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $frmcompetition = $this->extractFrm($html, 'frmcompetition');
        $results = [];
        $rows = $xpath->query("//table[@id='ctnResultats']//tr[td[contains(@class, 'datas0') or contains(@class, 'datas1')]]");
        foreach ($rows as $row) {
            $isDatas0 = ($xpath->evaluate("count(.//td[@class='datas0'])", $row) > 0);
            $cellClass = $isDatas0 ? 'datas0' : 'datas1';
            $cells = $xpath->query(".//td[@class='$cellClass']", $row);
            if ($cells->length >= 10) {
                $code = trim($cells->item(7)->textContent);
                $results[] = [
                    'date' => trim($cells->item(0)->textContent),
                    'name' => trim($cells->item(1)->textContent),
                    'competition' => trim($cells->item(2)->textContent),
                    'place' => trim($cells->item(4)->textContent),
                    'time' => trim($cells->item(5)->textContent),
                    'category' => $code,
                    'round' => trim($cells->item(8)->textContent),
                    'location' => trim($cells->item(9)->textContent),
                ];
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
