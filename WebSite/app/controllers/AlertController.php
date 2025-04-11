<?php

namespace app\controllers;

use app\helpers\Alert;
use DateTime;

class AlertController extends BaseController
{

    public function showAlerts()
    {
        if ($person = $this->getPerson(['Redactor'])) {
            $alerts = (new Alert($this->pdo))->getAlerts();

            $today = new DateTime();
            foreach ($alerts as &$alert) {
                $startDate = new DateTime($alert['StartDate']);
                $endDate = new DateTime($alert['EndDate']);

                if ($startDate <= $today && $endDate >= $today) {
                    $alert['Status'] = 'active';
                } elseif ($startDate > $today) {
                    $alert['Status'] = 'futur';
                } else {
                    $alert['Status'] = 'passée';
                }

                $alert['IsCreator'] = ($alert['CreatedBy'] == $person['Id']);
            }

            $alertTypes = [
                'alert-primary' => 'Primaire',
                'alert-secondary' => 'Secondaire',
                'alert-success' => 'Succès',
                'alert-danger' => 'Danger',
                'alert-warning' => 'Avertissement',
                'alert-info' => 'Information',
                'alert-light' => 'Clair',
                'alert-dark' => 'Sombre'
            ];

            echo $this->latte->render('app/views/alerts/index.latte', $this->params->getAll([
                'alerts' => $alerts,
                'alertTypes' => $alertTypes,
                'person' => $person,
                'navItems' => $this->getNavItems(),
                'groups' => $this->getGroups(),
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function updateAlert()
    {
        $person = $this->getPerson([]);
        if (!$person) {
            $this->application->error403(__FILE__, __LINE__);
            return;
        }

        $alertId = $this->flight->request()->data->id;
        $message = $this->flight->request()->data->message;
        $type = $this->flight->request()->data->type;
        $startDate = $this->flight->request()->data->startDate;
        $endDate = $this->flight->request()->data->endDate;
        $onlyForMembers = isset($this->flight->request()->data->onlyForMembers) ? 1 : 0;

        $alertManager = new Alert($this->pdo);

        // Vérifier que l'utilisateur est bien le créateur de l'alerte
        $alert = $alertManager->getAlertById($alertId);
        if (!$alert || $alert['CreatedBy'] != $person['Id']) {
            $this->application->error403(__FILE__, __LINE__);
            return;
        }

        $result = $alertManager->updateAlert($alertId, $message, $type, $startDate, $endDate, $onlyForMembers);

        if ($result) {
            $this->flight->redirect('/alerts');
        } else {
            // Gérer l'erreur
            echo $this->latte->render('app/views/error.latte', $this->params->getAll([
                'message' => 'Erreur lors de la mise à jour de l\'alerte.'
            ]));
        }
    }
}
