<?php

namespace app\apis;

use Exception;
use app\helpers\LogDataHelper;
use app\helpers\PageDataHelper;
use app\helpers\PersonGroupDataHelper;

class WebmasterApi extends BaseApi
{
    private PageDataHelper $pageDataHelper;
    private PersonGroupDataHelper $personGroupDataHelper;

    public function __construct()
    {
        $this->pageDataHelper = new PageDataHelper();
        $this->personGroupDataHelper = new PersonGroupDataHelper();
    }

    public function addToGroup($personId, $groupId)
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {
            try {
                $this->renderJson(['success' => $this->personGroupDataHelper->add($personId, $groupId)]);
            } catch (Exception $e) {
                $this->renderJson(['error' => $e->getMessage()], 500);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function getPersonsInGroup($id)
    {
        if ($this->personDataHelper->getPerson([])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                try {
                    $users = $this->personDataHelper->getPersonsInGroup($id);
                    $this->renderJson($users);
                } catch (Exception $e) {
                    $this->renderJson(['error' => $e->getMessage()], 500);
                }
            } else $this->renderJson(['success' => false, 'message' => 'Bad request method'], 470);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function lastVersion()
    {
        (new LogDataHelper())->add();
        $this->renderJson(['lastVersion' => $this->application->getVersion()]);
    }

    public function removeFromGroup($personId, $groupId)
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {
            try {
                $this->renderJson(['success' => $this->personGroupDataHelper->del($personId, $groupId)]);
            } catch (Exception $e) {
                $this->renderJson(['error' => $e->getMessage()], 500);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    #region Navbar
    public function deleteNavbarItem($id)
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            try {
                $result = $this->pageDataHelper->del($id);
                $this->renderJson(['success' => $result == 1]);
            } catch (Exception $e) {
                $this->renderJson(['error' => $e->getMessage()], 500);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function getNavbarItem($id)
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            try {
                $this->renderJson(['success' => true, 'message' => $this->pageDataHelper->get_($id)]);
            } catch (Exception $e) {
                $this->renderJson(['error' => $e->getMessage()], 500);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function saveNavbarItem()
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['name']) || empty($data['route'])) {
                $this->renderJson(['success' => false, 'message' => 'Name and Route are required']);
                return;
            }
            try {
                $this->pageDataHelper->insertOrUpdate($data);
                $this->renderJson(['success' => true]);
            } catch (Exception $e) {
                $this->renderJson(['error' => $e->getMessage()], 500);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function updateNavbarPositions()
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            $data = json_decode(file_get_contents('php://input'), true);
            try {
                $this->pageDataHelper->updates($data['positions']);
                $this->renderJson(['success' => true]);
            } catch (Exception $e) {
                $this->renderJson(['error' => $e->getMessage()], 500);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }
}
