<!doctype html>
<html lang="fr">
    <head>
        <style>
            .visitor-grid {
                margin: 20px;
                font-family: Arial, sans-serif;
            }

            .filter-form {
                margin-bottom: 20px;
            }

            .filter-form input {
                margin-right: 10px;
                padding: 5px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }

            th {
                background-color: #f5f5f5;
                text-align: left;
            }

            tr:nth-child(even) {
                background-color: #fafafa;
            }

            .pagination {
                text-align: center;
            }

            .pagination a {
                display: inline-block;
                padding: 8px 16px;
                text-decoration: none;
                color: #000;
                border: 1px solid #ddd;
                margin: 0 4px;
            }

            .pagination a:hover:not(.active) {
                background-color: #ddd;
            }

            .pagination .active {
                background-color: #4CAF50;
                color: white;
                border: 1px solid #4CAF50;
            }

            .pagination .disabled {
                color: #ddd;
                pointer-events: none;
            }
        </style>
    </head>
<body>

<?php

require_once 'Display.php';
require_once 'VisitorGraphs.php';
require_once __DIR__ . '/../../includes/Globals.php';


$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$filters = [
    'Os' => isset($_GET['os']) ? $_GET['os'] : '',
    'Browser' => isset($_GET['browser']) ? $_GET['browser'] : '',
    'Type' => isset($_GET['type']) ? $_GET['type'] : '',
    'Who' => isset($_GET['email']) ? $_GET['email'] : ''
];
echo $css;
echo (new Display())->displayGrid($currentPage, $filters);
(new VisitorGraph())->draw();
echo '<img src="'. CLIENT_TYPES_GRAPH .'" alt="Pie graph"/>';

require __DIR__ . '/../../includes/footer.php';
?>