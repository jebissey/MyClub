<?php

namespace app\modules\Article;

use RuntimeException;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\enums\ApplicationError;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\DesignDataHelper;
use app\modules\Common\AbstractController;

class DesignController extends AbstractController
{

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function index()
    {
        if (!($this->connectedUser->get()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        [$designs, $userVotes] = (new DesignDataHelper($this->application))->getUsersVotes($this->connectedUser->person->Id);

        $this->render('Article/views/designs_index.latte', Params::getAll([
            'designs' => $designs,
            'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            'userVotes' => $userVotes
        ]));
    }

    public function create()
    {
        if (!($this->connectedUser->get()->isHomeDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Article/views/design_create.latte', Params::getAll([
            'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
        ]));
    }

    public function save()
    {
        if (!($this->connectedUser->get()->isHomeDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $schema = [
            'id' => FilterInputRule::Int->value,
            'name' => FilterInputRule::HtmlSafeName->value,
            'detail' => FilterInputRule::HtmlSafeName->value,
            'navbar' => FilterInputRule::Content->value,
            'onlyForMembers' => FilterInputRule::Int->value,
            'idGroup' => FilterInputRule::Int->value,
        ];
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $values = [
            'IdPerson' => $this->connectedUser->person->Id,
            'Name' => $filterValues['name'] ?? '',
            'Detail' => $filterValues['detail'] ?? '',
            'NavBar' => $filterValues['navbar'] ?? '',
            'Status' => 'UnderReview',
            'OnlyForMembers' => $filterValues['onlyForMembers'] ?? 1,
            'IdGroup' => $filterValues['idGroup']
        ];
        $this->dataHelper->set('Design', $values, ['Id' => $filterValues['id'] ?? throw new RuntimeException('Missing Id in file ' . __FILE__ . ' at line ' . __LINE__)]);

        $this->redirect('/designs');
    }
}
