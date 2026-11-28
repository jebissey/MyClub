<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\modules\Common\interfaces\NewsProviderInterface;
use app\modules\Common\valueObjects\PresentationNewsRow;

/**
 * @phpstan-import-type NewsFeedItem from NewsProviderInterface
 * @phpstan-import-type PresentationNewsRowShape from PresentationNewsRow
 */
final class PresentationNewsDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * @return array<int, array{type: string, id: int, title: string, date: string, url: string}>
     */
    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if (!($connectedUser->person ?? false)) {
            return $news;
        }

        $sql = "
            SELECT i.Id, i.Email, i.FirstName, i.LastName, m.PresentationLastUpdate
            " . PersonDataHelper::MEMBER_INDIVIDUAL_JOIN . "
            WHERE m.InPresentationDirectory = 1
              AND m.PresentationLastUpdate >= :searchFrom
              AND i.Email != :email
            ORDER BY m.PresentationLastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':searchFrom' => $searchFrom,
            ':email'      => $connectedUser->person->email
        ]);

        /** @var list<PresentationNewsRowShape> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($rows as $row) {
            $presentation = PresentationNewsRow::fromStdClass($row);

            $fullName = trim($presentation->firstName . ' ' . $presentation->lastName);
            if (empty($fullName)) {
                $fullName = $presentation->email;
            }
            $news[] = [
                'type'  => 'presentation',
                'id'    => $presentation->id,
                'title' => 'Présentation de ' . $fullName,
                'date'  => $presentation->presentationLastUpdate,
                'url'   => '/user/presentation/' . $presentation->id
            ];
        }
        return $news;
    }
}
