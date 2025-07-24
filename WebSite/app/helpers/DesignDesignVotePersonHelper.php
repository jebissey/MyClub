<?php

namespace app\helpers;

class DesignDesignVotePersonHelper extends Data
{
    public function getUsersVotes($personId)
    {
        $query = "SELECT d.Id, d.Name, d.Detail, d.NavBar, d.Status, d.OnlyForMembers, d.IdGroup, 
            p.FirstName || ' ' || p.LastName || CASE WHEN p.NickName IS NOT NULL AND p.NickName != '' 
                                                        THEN ' (' || p.NickName || ')' 
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
            JOIN Person p ON d.IdPerson = p.Id
            GROUP BY d.Id";
        $designs = $this->pdo->query($query)->fetchAll();
        
        $userVotes = [];
        $votes = $this->gets('DesignVote', ['IdPerson' => $personId]);
        foreach ($votes as $vote) {
            $userVotes[$vote->IdDesign] = $vote->Vote;
        }
        return [$designs, $userVotes];
    }
}
