<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\modules\Common\interfaces\NewsProviderInterface;

/**
 * Provides the "member presentation updated" news items.
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

        /** @var array<int, object{Id: int, Email: string, FirstName: string, LastName: string, PresentationLastUpdate: string}> $presentations */
        $presentations = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($presentations as $presentation) {
            $fullName = trim($presentation->FirstName . ' ' . $presentation->LastName);
            if (empty($fullName)) {
                $fullName = $presentation->Email;
            }
            $news[] = [
                'type'  => 'presentation',
                'id'    => $presentation->Id,
                'title' => 'Présentation de ' . $fullName,
                'date'  => $presentation->PresentationLastUpdate,
                'url'   => '/user/presentation/' . $presentation->Id
            ];
        }
        return $news;
    }
}
