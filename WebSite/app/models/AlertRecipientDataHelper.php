<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;
use app\helpers\NewArticleAlertPreference;
use app\helpers\PersonPreferences;

/**
 * Resolves who must be alerted (new events, new articles).
 *
 * @phpstan-import-type PersonGroupRow from PersonDataHelper
 */
final class AlertRecipientDataHelper extends Data
{
    public function __construct(
        Application $application,
        private readonly PersonDataHelper $personDataHelper,
        private readonly PersonPreferences $personPreferences
    ) {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * @return string[]
     */
    public function getEmailsOfInterestedPeople(?int $idGroup, ?int $idEventType, ?int $dayOfWeek, string $timeOfDay): array
    {
        $persons = $this->getInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
        $filteredEmails = [];
        foreach ($persons as $person) {
            if ($person->Email !== null) {
                $filteredEmails[] = $person->Email;
            }
        }
        return $filteredEmails;
    }

    /**
     * @return list<PersonGroupRow>
     */
    public function getInterestedPeople(?int $idGroup, ?int $idEventType, ?int $dayOfWeek, string $timeOfDay): array
    {
        $persons = $this->personDataHelper->getPersonsInGroup($idGroup);
        $filteredPeople = [];
        foreach ($persons as $person) {
            if ($person->Email === null) {
                continue;
            }
            if (
                $this->personPreferences->isPersonInterested(
                    $person->Preferences,
                    $person->Availabilities,
                    $idEventType,
                    $dayOfWeek,
                    $timeOfDay
                )
            ) {
                $filteredPeople[] = $person;
            }
        }
        return $filteredPeople;
    }

    /**
     * Returns the emails of the members who want to be alerted about the article
     * and records one Message per recipient.
     *
     * @return string[]
     */
    public function getPersonWantedToBeAlerted(int $idArticle): array
    {
        $idGroup = null;
        $group = $this->get('Article', ['Id' => $idArticle], 'IdGroup');
        if ($group !== false) {
            /** @var object{IdGroup: int} $group */
            $idGroup = $group->IdGroup;
        }

        $survey = $this->get('Survey', ['IdArticle' => $idArticle], 'Id');
        $order = $this->get('Order', ['IdArticle' => $idArticle], 'Id');
        $hasSurvey = $survey !== false;
        $hasOrder = $order !== false;

        $filteredEmails = [];
        foreach ($this->personDataHelper->getPersonsInGroup($idGroup) as $person) {
            if ($person->Email === null) {
                continue;
            }
            if (!NewArticleAlertPreference::wantsAlert($person->Preferences, $hasSurvey, $hasOrder)) {
                continue;
            }
            $filteredEmails[] = $person->Email;
            $this->set('Message', [
                'EventId'  => null,
                'PersonId' => $person->PersonId,
                'Text'     => "Nouvel article publié\n\n/article/{$idArticle} (->{$person->Email})",
                'From'     => 'Webapp'
            ]);
        }
        return $filteredEmails;
    }
}
