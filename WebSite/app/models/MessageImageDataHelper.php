<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use RuntimeException;
use app\helpers\Application;
use app\helpers\To;

/**
 * Resolves and tracks the images attached to messages.
 */
final class MessageImageDataHelper extends Data
{
    public function __construct(Application $application, private readonly LanguagesDataHelper $languagesDataHelper)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /** @return array{0: int, 1: int, 2: string} year, month, filename */
    public function getImageInfo(int $messageId): array
    {
        $message = $this->get('Message', ['Id' => $messageId], 'ImagePath');
        if (!$message) {
            throw new RuntimeException(
                $this->languagesDataHelper->translate('message.image_not_found')
            );
        }
        if (empty($message->ImagePath)) {
            throw new RuntimeException(
                $this->languagesDataHelper->translate('message.image_not_attached')
            );
        }
        $path = is_string($message->ImagePath)
            ? parse_url($message->ImagePath, PHP_URL_PATH)
            : false;

        if (!is_string($path) || $path === '') {
            throw new RuntimeException(
                $this->languagesDataHelper->translate('message.image_invalid_path')
            );
        }
        $filename = basename($path);
        $month = basename(dirname($path));
        $year = basename(dirname(dirname($path)));
        if (!is_numeric($year) || !is_numeric($month) || empty($filename)) {
            throw new RuntimeException(
                $this->languagesDataHelper->translate('message.image_invalid_structure')
            );
        }
        return [
            To::requireInt($year),
            To::requireInt($month),
            $filename,
        ];
    }

    /**
     * @return array{
     *     events: list<object{Id: int, Title: string, StartTime: string}>,
     *     articles: list<object{Id: int, Title: string}>,
     *     groups: list<object{Id: int, Title: string}>
     * }
     */
    public function getUses(string $path): array
    {
        $like = '%' . $path . '%';

        $events = $this->pdo->prepare("
            SELECT DISTINCT e.Id, e.Summary AS Title, e.StartTime
            FROM Message m
            JOIN Event e ON e.Id = m.EventId
            WHERE m.EventId IS NOT NULL
              AND m.ImagePath LIKE :path
            ORDER BY e.StartTime DESC
        ");
        $events->execute([':path' => $like]);

        $articles = $this->pdo->prepare("
            SELECT DISTINCT a.Id, a.Title
            FROM Message m
            JOIN Article a ON a.Id = m.ArticleId
            WHERE m.ArticleId IS NOT NULL
              AND m.ImagePath LIKE :path
            ORDER BY a.Title ASC
        ");
        $articles->execute([':path' => $like]);

        $groups = $this->pdo->prepare("
            SELECT DISTINCT g.Id, g.Name AS Title
            FROM Message m
            JOIN `Group` g ON g.Id = m.GroupId
            WHERE m.GroupId IS NOT NULL
              AND m.ImagePath LIKE :path
            ORDER BY g.Name ASC
        ");
        $groups->execute([':path' => $like]);

        /** @var list<object{Id: int, Title: string, StartTime: string}> $eventsResult */
        $eventsResult = $events->fetchAll(PDO::FETCH_OBJ);

        /** @var list<object{Id: int, Title: string}> $articlesResult */
        $articlesResult = $articles->fetchAll(PDO::FETCH_OBJ);

        /** @var list<object{Id: int, Title: string}> $groupsResult */
        $groupsResult = $groups->fetchAll(PDO::FETCH_OBJ);

        return [
            'events'   => $eventsResult,
            'articles' => $articlesResult,
            'groups'   => $groupsResult,
        ];
    }

    /**
     * @param array<int, string> $paths
     * @return array<string, bool> the subset of $paths referenced by at least one message
     */
    public function getUsedPaths(array $paths): array
    {
        if (empty($paths)) {
            return [];
        }

        $stmt = $this->pdo->query("SELECT ImagePath FROM Message WHERE ImagePath IS NOT NULL");
        if ($stmt === false) {
            return [];
        }
        $imagePaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $used = [];
        foreach ($paths as $path) {
            foreach ($imagePaths as $imagePath) {
                if (is_string($imagePath) && str_contains($imagePath, $path)) {
                    $used[$path] = true;
                    break;
                }
            }
        }
        return $used;
    }
}
