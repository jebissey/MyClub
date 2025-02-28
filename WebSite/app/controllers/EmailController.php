<?php

namespace app\controllers;

use PDO;

class EmailController extends BaseController
{
    public function fetchEmails()
    {
        if ($this->getPerson(['EventManager'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $idGroup = $_POST['idGroup'] ?? '';
                $idEventType = $_POST['idEventType'] ?? '';
                $dayOfWeek = $_POST['dayOfWeek'] ?? '';
                $timeOfDay = $_POST['timeOfDay'] ?? '';
                $innerJoin = $and = '';
                if (!empty($idGroup)) {
                    $innerJoin = 'INNER JOIN PersonGroup on PersonGroup.IdPerson = Person.Id';
                    $and = 'AND PersonGroup.IdGroup = ' . $idGroup;
                }
                $query = $this->pdo->query("
                    SELECT Email, Preferences, Availabilities
                    FROM Person
                    $innerJoin
                    WHERE Person.Inactivated = 0 $and
                ");
                $persons = $query->fetchAll(PDO::FETCH_ASSOC);
                $filteredEmails = [];
                foreach ($persons as $person) {
                    $include = true;
                    if (!empty($idEventType)) {
                        if ($person['Preferences'] ?? '' != '') {
                            $preferences = json_decode($person['Preferences'] ?? '', true);
                            if ($preferences != '' && !isset($preferences['eventTypes'][$idEventType])) {
                                $include = false;
                            }
                        }
                    }
                    if (!empty($dayOfWeek)) {
                        if ($person['Availabilities'] ?? '' != '') {
                            $availabilities = json_decode($person['Availabilities'] ?? '', true);
                            if (empty($timeOfDay)) {
                                if (!isset($availabilities[$dayOfWeek])) {
                                    $include = false;
                                }
                            } else {
                                $timeKey = match ($timeOfDay) {
                                    'morning' => 'morning',
                                    'afternoon' => 'afternoon',
                                    'evening' => 'evening',
                                    default => ''
                                };

                                if (
                                    !isset($availabilities[$dayOfWeek][$timeKey])
                                    || $availabilities[$dayOfWeek][$timeKey] !== 'on'
                                ) {
                                    $include = false;
                                }
                            }
                        }
                    }
                    if ($include) {
                        $filteredEmails[] = $person['Email'];
                    }
                }
                echo $this->latte->render('app/views/emails/copyToClipBoard.latte', $this->params->getAll([
                    'emailsJson' => json_encode($filteredEmails),
                    'emails' => $filteredEmails
                ]));

            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {

                echo $this->latte->render('app/views/emails/getEmails.latte', $this->params->getAll([
                    'groups' => $this->fluent->from("'Group'")->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
                    'eventTypes' => $this->fluent->from('EventType')->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
