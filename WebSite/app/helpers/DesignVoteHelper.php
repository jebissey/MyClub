<?php

namespace app\helpers;


class DesignVoteHelper extends Data
{
    public function insertOrUpdate($data, $personId)
    {
        $designId = (int)$data['designId'] ?? 0;
        $userId = $personId;
        $voteValue = $data['vote'] ?? 'voteNeutral';

        $existingVote = $this->fluent->from('DesignVote')
            ->where('IdDesign', $designId)
            ->where('IdPerson', $userId)
            ->fetch();
        if ($existingVote) {
            $this->fluent->update('DesignVote')
                ->set(['Vote' => $voteValue])
                ->where('Id', $existingVote->Id)
                ->execute();
        } else {
            $this->fluent->insertInto('DesignVote')
                ->values([
                    'IdDesign' => $designId,
                    'IdPerson' => $userId,
                    'Vote' => $voteValue
                ])
                ->execute();
        }
    }
}
