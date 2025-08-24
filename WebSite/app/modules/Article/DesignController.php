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
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            [$designs, $userVotes] = (new DesignDataHelper($this->application))->getUsersVotes($this->connectedUser->person->Id);

            $this->render('Article/views/designs_index.latte', Params::getAll([
                'designs' => $designs,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'userVotes' => $userVotes
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function create()
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            $this->render('Article/views/design_create.latte', Params::getAll([
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function save()
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $this->dataHelper->set('Design', $values, ['Id'=> $filterValues['id'] ?? throw new RuntimeException('Missing Id in file ' . __FILE__ . ' at line ' . __LINE__)]);

                $this->redirect('/designs');
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
