<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class ReplyDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function insertOrUpdate(int $personId, int $surveyId, string $answers): void
    {
        $existingReply = $this->get(
            'Reply',
            ['IdPerson' => $personId, 'IdSurvey' => $surveyId],
            'Id'
        );

        if ($existingReply) {
            $replyData = get_object_vars($existingReply);

            $this->set('Reply', [
                'Answers' => $answers,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ], [
                'Id' => (int) $replyData['Id'],
            ]);
        } else {
            $this->set('Reply', [
                'IdPerson' => $personId,
                'IdSurvey' => $surveyId,
                'Answers' => $answers,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
