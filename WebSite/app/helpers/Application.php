<?php

namespace app\helpers;

use flight\Engine;
use Latte\Engine as LatteEngine;
use PDO;
use RuntimeException;
use Throwable;

use app\models\Database;
use app\models\DataHelper;
use app\models\PersonDataHelper;

class Application
{
    public const VERSION = '0.6.0';
    public const  EMOJI_LIST = [
        '😀', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🤨', 
        '🙂', '🙃', '😉', '😌', '☹️', '😐', '🙄', '😯', '🥴', 
        '🤩', '😍', '🥰', '😘', '😚', '🧐', '🤓', '😎', '🥸', 
        '🫣', '🤗', '🫢', '🤭', '🤫', '🤔', '🫡', '🥱', '😴', 
        '😋', '😛', '🤪', '🤮', '🤧', '😷', '🤒', '🤕', '🤐', 
        '😥', '😭', '😤', '😠', '🥵', '🥶', '🤑', '🤠', '🥳', 
    ];

    private static self $instance;
    private static Engine $flight;
    private static LatteEngine $latte;
    public static string $root;

    private PDO $pdo;
    private PDO $pdoForLog;
    private DataHelper $dataHelper;
    private PersonDataHelper $personDataHelper;

    private ErrorManager $errorManager;

    private function __construct()
    {
        self::$root = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        self::$flight = new Engine();
        self::$latte = new LatteEngine();
        self::$latte->setTempDirectory(__DIR__ . '/../../var/latte/temp');
        $this->setupLatteFilters();

        try {
            $db = Database::getInstance();
            $this->pdo = $db->getPdo();
            $this->pdoForLog = $db->getPdoForLog();
            $this->dataHelper = new DataHelper($this);
            $this->personDataHelper = new PersonDataHelper($this);
        } catch (Throwable $e) {
            throw new RuntimeException('Database error ' . $e->getMessage() . ' in file ' . __FILE__ . ' at line ' . __LINE__);
        }
        $this->errorManager = new ErrorManager($this);
    }

    public static function init(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getFlight(): Engine
    {
        return self::$flight;
    }

    public function getLatte(): LatteEngine
    {
        return self::$latte;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getPdoForLog(): PDO
    {
        return $this->pdoForLog;
    }

    public function getErrorManager(): ErrorManager
    {
        return $this->errorManager;
    }

    public function getDataHelper(): DataHelper
    {
        return $this->dataHelper;
    }


    public function getPersonDataHelper(): PersonDataHelper
    {
        return $this->personDataHelper;
    }

    public function enumToValues(string $enumClass): array
    {
        return array_map(fn($case) => $case->value, $enumClass::cases());
    }

    #region Private functions
    private function setupLatteFilters(): void
    {
        self::$latte->addExtension(new \Latte\Bridges\Tracy\TracyExtension);

        self::$latte->addFilter('json', function ($value) {
            return json_encode($value, JSON_HEX_APOS | JSON_HEX_QUOT);
        });

        self::$latte->addFilter('extractFirstElement', function ($html) {
            if (preg_match('/<p[^>]*>(.*?)<\/p>/s', $html, $matches)) {
                return $matches[0];
            }
            if (preg_match('/<img[^>]*>/i', $html, $matches)) {
                return $matches[0];
            }
            if (preg_match('/<a[^>]*>.*?<\/a>/i', $html, $matches)) {
                return $matches[0];
            }
            $text = strip_tags($html);
            return strlen($text) > 150 ? substr($text, 0, 150) . '...' : $text;
        });

        self::$latte->addFilter('nl2br', function ($string) {
            return nl2br(htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        });

        self::$latte->addFilter('urlencode', function ($s) {
            return urlencode($s);
        });
    }
}
