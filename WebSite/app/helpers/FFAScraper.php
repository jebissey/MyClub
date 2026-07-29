<?php

declare(strict_types=1);

namespace app\helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use DOMDocument;
use DOMNameSpaceNode;
use DOMNode;
use DOMXPath;

class FFAScraper
{
    private string $baseUrl = 'https://bases.athle.fr/asp.net/liste.aspx';
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 5,
            'headers' => [
                'User-Agent' =>
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3'
            ]
        ]);
    }

    /**
     * @return array{rank: string, event: string, name: string, club: string, points: string}|array{error: string}|null
     */
    public function searchAthleteRank(string $firstName, string $lastName, string $year, string $club): ?array
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

    /**
     * @return array<int, array{
     *     date: string, name: string, competition: string, place: string,
     *     time: string, category: string, round: string, location: string
     * }>|array{error: string}
     */
    public function searchAthleteResults(string $firstName, string $lastName, string $year, string $club): array
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

    /**
     * @return array{rank: string, event: string, name: string, club: string, points: string}|null
     */
    private function parseAthleteRank(string $html): ?array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query("//table[@id='ctnCoupe']//tr[td[@class='datas0']]");
        if ($rows === false) {
            return null;
        }

        foreach ($rows as $row) {
            if (!$row instanceof DOMNode) {
                continue;
            }
            $dataCells = $xpath->query(".//td[@class='datas0']", $row);
            if ($dataCells === false) {
                continue;
            }
            if ($dataCells->length >= 5) {
                return [
                    'rank' => $this->cellText($dataCells->item(0)),
                    'event' => $this->cellText($dataCells->item(1)),
                    'name' => $this->cellText($dataCells->item(2)),
                    'club' => $this->cellText($dataCells->item(3)),
                    'points' => $this->cellText($dataCells->item(4))
                ];
            }
        }
        return null;
    }

    /**
     * @return array<int, array{
     *     date: string, name: string, competition: string, place: string,
     *     time: string, category: string, round: string, location: string
     * }>
     */
    private function parseAthleteResults(string $html): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $results = [];
        $rows = $xpath->query("//table[@id='ctnResultats']//tr[td[contains(@class, 'datas0') or contains(@class, 'datas1')]]");
        if ($rows === false) {
            return $results;
        }
        foreach ($rows as $row) {
            if (!$row instanceof DOMNode) {
                continue;
            }
            $isDatas0 = ($xpath->evaluate("count(.//td[@class='datas0'])", $row) > 0);
            $cellClass = $isDatas0 ? 'datas0' : 'datas1';
            $cells = $xpath->query(".//td[@class='$cellClass']", $row);
            if ($cells === false) {
                continue;
            }
            if ($cells->length >= 10) {
                $code = $this->cellText($cells->item(7));
                $results[] = [
                    'date' => $this->cellText($cells->item(0)),
                    'name' => $this->cellText($cells->item(1)),
                    'competition' => $this->cellText($cells->item(2)),
                    'place' => $this->cellText($cells->item(4)),
                    'time' => $this->cellText($cells->item(5)),
                    'category' => $code,
                    'round' => $this->cellText($cells->item(8)),
                    'location' => $this->cellText($cells->item(9)),
                ];
            }
        }

        return $results;
    }

    private function cellText(DOMNameSpaceNode|DOMNode|null $node): string
    {
        return $node instanceof DOMNode ? trim($node->textContent) : '';
    }
}
