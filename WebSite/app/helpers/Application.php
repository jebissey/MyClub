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
use UnitEnum;
use app\exceptions\DatabaseException;
use app\exceptions\UnreachableException;
use app\models\AuthorizationDataHelper;
use app\models\Database;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\LogCompactDataHelper;
use app\models\LogWriterDataHelper;
use app\models\MetadataDataHelper;
use app\modules\Common\services\AuthenticationService;
use app\modules\Common\valueObjects\CompactSettingsRow;
use app\modules\Common\valueObjects\Person;

final class Application
{
    public const VERSION = '0.100';

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

    private readonly PDO $pdo;
    private readonly PDO $pdoForLog;
    private readonly ErrorManager $errorManager;
    private readonly ConnectedUser $connectedUser;
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
            throw new DatabaseException(
                'Database error ' . $e->getMessage() . ' in ' . $e->getFile() . ' at ' . $e->getLine(),
                previous: $e
            );
        }
    }

    public static function init(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Compacts the log according to the settings stored in Metadata.
     * Must only be called once init() has returned, i.e. once the database is up to date.
     */
    public function compactLogIfNeeded(): void
    {
        $row = new DataHelper($this->getPdo(), $this->getErrorManager(), $this->getPdoForLog())
            ->get('Metadata', ['Id' => 1], 'Compact_everyXdays, Compact_removeOlderThanXmonths, Compact_compactOlderThanXmonths');
        if ($row === false) {
            self::unreachable('Missing metadata');
        }
        /** @var stdClass $row */
        $metadata = CompactSettingsRow::fromStdClass($row);
        new LogCompactDataHelper($this)->compactLog(
            $metadata->Compact_removeOlderThanXmonths,
            $metadata->Compact_compactOlderThanXmonths
        );
    }

    /**
     * Returns the connected user.
     *
     * When $requirePerson is true, the caller asserts that a person is connected
     * (typically after an isConnected() check). A LogicException is thrown otherwise,
     * and static analysis sees ConnectedUser::$person as Person instead of Person|null.
     *
     * @param bool $requirePerson Whether a connected person is mandatory
     * @return ($requirePerson is true ? ConnectedUser&object{person: Person} : ConnectedUser)
     * @throws LogicException If $requirePerson is true and nobody is connected
     */
    public function getConnectedUser(bool $requirePerson = false): ConnectedUser
    {
        if ($requirePerson && $this->connectedUser->person === null) {
            throw new LogicException('No connected person: isConnected() must be checked before requiring one.');
        }

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

    public function getPdoForCredential(): PDO
    {
        return Database::getInstance()->getPdoForCredential();
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
     * @throws UnreachableException Always thrown
     */
    public static function unreachable(mixed $value = null, bool $log = true): never
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;

        $msg = "Unreachable code executed";
        if ($value !== null) {
            if ($value instanceof UnitEnum) {
                $msg .= " (enum " . $value::class . "::" . $value->name . ")";
            } elseif (is_object($value)) {
                $msg .= " (object of type " . $value::class . ")";
            } else {
                $msg .= " (value: " . var_export($value, true) . ")";
            }
        }

        if ($log) {
            try {
                $pdoForLog = Database::getPdoForLogIfAvailable();
                if ($pdoForLog !== null) {
                    LogWriterDataHelper::insert($pdoForLog, 'UNREACHABLE', "{$msg} in file {$file} at line {$line}");
                }
            } catch (Throwable) {
                // A logging failure must never hide the original error.
            }
        }

        throw new UnreachableException($msg, $file, $line);
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

        self::$latte->addFilter(
            'readableDuration',
            fn($duration) => TranslationManager::getReadableDuration($duration)
        );
    }
}
