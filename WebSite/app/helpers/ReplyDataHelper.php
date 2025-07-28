<?php

namespace app\helpers;

class ReplyDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function insertOrUpdate($personId, $surveyId,  $answers)
    {
        $existingReply = $this->fluent->from('Reply')
            ->where('IdPerson', $personId)
            ->where('IdSurvey', $surveyId)
            ->fetch();
        if ($existingReply) {
            $this->fluent->update('Reply')
                ->set([
                    'Answers' => $answers,
                    'LastUpdate' => date('Y-m-d H:i:s')
                ])
                ->where('Id', $existingReply->Id)
                ->execute();
        } else {
            $this->fluent->insertInto('Reply')
                ->values([
                    'IdPerson' => $personId,
                    'IdSurvey' => $surveyId,
                    'Answers' => $answers,
                    'LastUpdate' => date('Y-m-d H:i:s')
                ])
                ->execute();
        }
    }

    public function get_($surveyId, $personId)
    {
        return $this->fluent->from('Reply')
            ->where('IdSurvey', $surveyId)
            ->where('IdPerson', $personId)
            ->fetch();
    }
}
