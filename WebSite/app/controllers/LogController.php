<?php

namespace app\controllers;

use flight\Engine;
use PDO;

class LogController extends BaseController
{
    private PDO $pdoForLog;

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);
        $this->pdoForLog = \app\helpers\database\Database::getInstance()->getPdoForLog();
    }

    public function index()
    {
        $logPage = isset($_GET['logPage']) ? (int)$_GET['logPage'] : 1;
        $perPage = 10;
        $offset = ($logPage - 1) * $perPage;

        $whereClause = [];
        $params = [];

        $filters = [
            'type' => 'Type',
            'browser' => 'Browser',
            'os' => 'Os',
            'code' => 'Code',
        ];

        foreach ($filters as $param => $column) {
            if (isset($_GET[$param]) && !empty($_GET[$param])) {
                $whereClause[] = "$column LIKE ?";
                $params[] = '%' . $_GET[$param] . '%';
            }
        }

        $where = '';
        if (!empty($whereClause)) {
            $where = 'WHERE ' . implode(' AND ', $whereClause);
        }

        $query = $this->pdoForLog->prepare("SELECT COUNT(*) as total FROM Log $where");
        $query->execute($params);
        $total = $query->fetch(PDO::FETCH_ASSOC)['total'];

        $query = $this->pdoForLog->prepare("SELECT * FROM Log $where ORDER BY CreatedAt DESC LIMIT ? OFFSET ?");
        $allParams = array_merge($params, [$perPage, $offset]);
        $query->execute($allParams);
        $logs = $query->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = ceil($total / $perPage);

        echo $this->latte->render('app/views/logs/index.latte', [
            'logs' => $logs,
            'currentPage' => $logPage,
            'totalPages' => $totalPages,
            'filters' => $_GET,
            'page' => basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
        ]);
    }
}
