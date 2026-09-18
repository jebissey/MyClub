<?php

declare(strict_types=1);

namespace app\helpers;

use flight\Engine;
use Latte\Engine as LatteEngine;
use Latte\Loaders\FileLoader;
use BackedEnum;
use LogicException;
use PDO;
use stdClass;
use Throwable;
use app\exceptions\DatabaseException;
use app\models\AuthorizationDataHelper;
use app\models\Database;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\LogCompactDataHelper;
use app\models\LogWriterDataHelper;
use app\models\MetadataDataHelper;
use app\modules\Common\services\AuthenticationService;
use app\modules\Common\valueObjects\CompactSettingsRow;

final class Application
{
    public const VERSION = '0.90.1';

    // @formatter:off    
    public const  EMOJI_LIST = [
        '😀', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🤨',
        '🙂', '🙃', '😉', '😌', '☹️', '😐', '🙄', '😯', '🥴',
        '🤩', '😍', '🥰', '😘', '😚', '🧐', '🤓', '😎', '🥸',
        '🫣', '🤗', '🫢', '🤭', '🤫', '🤔', '🫡', '🥱', '😴',
        '😋', '😛', '🤪', '🤮', '🤧', '😷', '🤒', '🤕', '🤐',
        '😥', '😭', '😤', '😠', '🥵', '🥶', '🤑', '🤠', '🥳',
        '🧑‍⚕️', '🧑‍⚖️', '🧑‍🍳', '🧑‍🏫', '🧑‍🌾', '🧑‍🔧', '🧑‍🏭', '🧑‍💼', '🧑‍🔬', '🧑‍💻', '🧑‍🎤', '🧑‍🎨', '🧑‍✈️', '🧑‍🚀', '🧑‍🚒', '🧑‍🎄', '🧑‍🎓',
        '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁',
        '🐦', '🐧', '🕊️', '🦅', '🦆', '🦉', '🦩', '🦚', '🦜', '🐔', '🐣', '🐥', '🦢', '🦃', '🦤',
        '🐞', '🐝', '🦋', '🐜', '🦗', '🕷️', '🐛', '🐌', '🪱', '🦟', '🪰', '🪳', '🪲',
        '🌱', '🌿', '☘️', '🍀', '🌳', '🌲', '🌻', '🌺', '🌸', '🌼', '🌷', '🥀', '🍂', '🍁', '🪴'
    ];
    // @formatter:on

    private static self $instance;

    /** @var Engine<object> */
    private static Engine $flight;

    private static LatteEngine $latte;
    public static string $root;

    private PDO $pdo;
    private PDO $pdoForLog;
    private ErrorManager $errorManager;
    private ConnectedUser $connectedUser;
    private AuthenticationService $authenticationService;

    private function __construct()
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        self::$root = 'https://' . (is_string($host) ? $host : 'localhost');
        self::$flight = new Engine();
        self::$latte = new LatteEngine();
        self::$latte->setLoader(new FileLoader(dirname(__DIR__, 2) . '/app/modules'));
        self::$latte->setCacheDirectory(dirname(__DIR__, 2) . '/var/latte/temp');
        $this->setupLatteFilters();

        try {
            $db = Database::getInstance();
            $this->pdo = $db->getPdo();
            $this->pdoForLog = $db->getPdoForLog();
            $this->errorManager = new ErrorManager($this);

            $metadataDataHelper = new MetadataDataHelper($this);
            TranslationManager::setForcedLanguage($metadataDataHelper->getForcedLanguage());

            $this->connectedUser = new ConnectedUser(
                $this->getErrorManager(),
                new DataHelper($this->getPdo(), $this->getErrorManager(), $this->getPdoForLog()),
                new AuthorizationDataHelper($this),
                $metadataDataHelper,
                new GravatarHandler(),
            );
        } catch (Throwable $e) {
            throw new DatabaseException('Database error ' . $e->getMessage() . ' in ' . $e->getFile() . ' at ' . $e->getLine());
        }
    }

    public static function init(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        $row = new DataHelper(
            self::$instance->getPdo(),
            self::$instance->getErrorManager(),
            self::$instance->getPdoForLog()
        )->get('Metadata', ['Id' => 1], 'Compact_everyXdays, Compact_removeOlderThanXmonths, Compact_compactOlderThanXmonths');
        if ($row === false) {
            Application::unreachable("Missing metadata", __FILE__, __LINE__);
        }
        /** @var stdClass $row */
        $metadata = CompactSettingsRow::fromStdClass($row);
        new LogCompactDataHelper(
            self::$instance
        )->compactLog($metadata->Compact_removeOlderThanXmonths, $metadata->Compact_compactOlderThanXmonths);
        return self::$instance;
    }

    public function getConnectedUser(): ConnectedUser
    {
        return $this->connectedUser;
    }

    /**
     * @return Engine<object>
     */
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

    /**
     * @template T of \BackedEnum
     * @param class-string<T> $enumClass
     * @return list<string>
     */
    public function enumToValues(string $enumClass): array
    {
        return array_map(
            static fn(BackedEnum $case): string => (string) $case->value,
            $enumClass::cases()
        );
    }

    /**
     * Helper to signal an unreachable state.
     *
     * @param mixed $value Unexpected value (optional, useful for debugging)
     * @throws LogicException Always thrown
     * @return never
     */
    public static function unreachable(mixed $value, string $file, int $line, bool $log = true): never
    {
        $msg = "Unreachable code executed in file {$file} at line {$line}";
        if ($value !== null) {
            if ($value instanceof \UnitEnum) {
                $msg .= " (enum " . $value::class . "::" . $value->name . ")";
            } elseif (is_object($value)) {
                $msg .= " (object of type " . $value::class . ")";
            } else {
                $msg .= " (value: " . var_export($value, true) . ")";
            }
        }
        if ($log) {
            new LogWriterDataHelper(self::init(), self::init()->getErrorManager())->add('UNREACHABLE', $msg);
        }
        throw new LogicException($msg);
    }

    public function setAuthenticationService(AuthenticationService $service): void
    {
        $this->authenticationService = $service;
    }

    public function getAuthenticationService(): AuthenticationService
    {
        return $this->authenticationService;
    }

    #region Private functions
    private function setupLatteFilters(): void
    {
        self::$latte->addExtension(new \Latte\Bridges\Tracy\TracyExtension());

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
            return nl2br(
                htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        });

        self::$latte->addFilter('urlencode', function ($s) {
            return urlencode($s);
        });

        self::$latte->addFilter('translate', function ($key) {
            static $languagesDataHelper = null;

            $languagesDataHelper ??= new LanguagesDataHelper(
                $this,
                $this->getErrorManager()
            );

            return $languagesDataHelper->translate($key);
        });

        self::$latte->addFilter(
            'shortDate',
            fn($date) => TranslationManager::getShortDate($date)
        );

        self::$latte->addFilter(
            'longDate',
            fn($date) => TranslationManager::getLongDate($date)
        );

        self::$latte->addFilter(
            'longDateTime',
            fn($date) => TranslationManager::getLongDateTime($date)
        );

        self::$latte->addFilter(
            'shortDateTime',
            fn($date) => TranslationManager::getShortDateTime($date)
        );

        self::$latte->addFilter(
            'dayName',
            fn($date) => TranslationManager::getDayName($date)
        );

        self::$latte->addFilter('formatFileSize', function ($bytes) {
            if ($bytes >= 1073741824) {
                return number_format($bytes / 1073741824, 2) . ' GB';
            }

            if ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 2) . ' MB';
            }

            if ($bytes >= 1024) {
                return number_format($bytes / 1024, 2) . ' KB';
            }

            return $bytes . ' bytes';
        });

        self::$latte->addFilter('version', function (string $path): string {
            $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';

            if (!is_string($documentRoot)) {
                return $path;
            }

            $fullPath = $documentRoot . '/' . ltrim($path, '/');

            if (file_exists($fullPath)) {
                $mtime = filemtime($fullPath);

                if ($mtime !== false) {
                    return $path . '?v=' . $mtime;
                }
            }

            return $path;
        });
    }
}
