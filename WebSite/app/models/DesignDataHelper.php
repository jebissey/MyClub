<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use PDOException;
use stdClass;
use app\helpers\Application;

class DesignDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application->getPdo(), $application->getErrorManager(), $application->getPdo());
    }

    /**
     * @param array{designId?: int|string, vote?: string} $data
     */
    public function insertOrUpdate(array $data, int $personId): void
    {
        $designId = (int)($data['designId'] ?? 0);
        $userId = $personId;
        $voteValue = $data['vote'] ?? 'voteNeutral';

        $existingVote = $this->get('DesignVote', ['IdDesign' => $designId, 'IdPerson' => $userId], 'Id');
        if ($existingVote instanceof stdClass) {
            $this->set(
                'DesignVote',
                ['Vote' => $voteValue],
                ['Id' => $existingVote->Id]
            );
        } else {
            $this->set(
                'DesignVote',
                [
                    'IdDesign' => $designId,
                    'IdPerson' => $userId,
                    'Vote' => $voteValue
                ]
            );
        }
    }

    /**
     * @return array{0: array<int, stdClass>, 1: array<int, string>}
     */
    public function getUsersVotes(int $personId): array
    {
        $query = "SELECT d.Id, d.Name, d.Detail, d.NavBar, d.Status, d.OnlyForMembers, d.IdGroup, 
            i.FirstName || ' ' || i.LastName || CASE WHEN i.NickName IS NOT NULL AND i.NickName != '' 
                THEN ' (' || i.NickName || ')' 
                ELSE '' 
                END AS NameOfDesigner,
            CASE WHEN COUNT(CASE WHEN dv.Vote = 'voteUp' THEN 1 END) = 0 
                AND COUNT(CASE WHEN dv.Vote = 'voteDown' THEN 1 END) = 0
                AND COUNT(CASE WHEN dv.Vote = 'voteNeutral' THEN 1 END) = 0
                THEN '0/0'
                ELSE COUNT(CASE WHEN dv.Vote = 'voteUp' THEN 1 END) || ' / ' || 
                    (COUNT(CASE WHEN dv.Vote = 'voteUp' THEN 1 END) + COUNT(CASE WHEN dv.Vote = 'voteDown' THEN 1 END)) ||
            CASE 
                WHEN COUNT(CASE WHEN dv.Vote = 'voteNeutral' THEN 1 END) > 0 
                THEN ' (+' || COUNT(CASE WHEN dv.Vote = 'voteNeutral' THEN 1 END) || ')' 
                ELSE '' 
            END
            END AS Votes
            FROM Design d
            LEFT JOIN DesignVote dv ON d.Id = dv.IdDesign
            JOIN Individual i ON d.IdPerson = i.Id
            GROUP BY d.Id";
        $stmt = $this->pdo->query($query);
        if ($stmt === false) {
            throw new PDOException('Échec de la requête getUsersVotes');
        }
        $designs = $stmt->fetchAll(PDO::FETCH_OBJ);

        $userVotes = [];
        $votes = $this->gets('DesignVote', ['IdPerson' => $personId]);
        foreach ($votes as $vote) {
            $userVotes[$vote->IdDesign] = $vote->Vote;
        }
        return [$designs, $userVotes];
    }

    /**
     * @return array<int, stdClass>
     */
    public function getPendingDesignResponses(): array
    {
        $query = "
        SELECT 
            i.Id AS PersonId, 
            i.Email, 
            d.Id AS DesignId, 
            d.Name AS DesignName,
            d.Detail AS DesignDetail
        FROM Individual i
        INNER JOIN Member m ON m.Id = i.Id
        CROSS JOIN Design d
        LEFT JOIN DesignVote dv ON dv.IdDesign = d.Id AND dv.IdPerson = i.Id
        WHERE m.Inactivated = 0
            AND d.Status = 'UnderReview'
            AND dv.Id IS NULL
        ORDER BY d.LastUpdate";

        $stmt = $this->pdo->query($query);
        if ($stmt === false) {
            throw new PDOException('Échec de la requête getPendingDesignResponses');
        }
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
