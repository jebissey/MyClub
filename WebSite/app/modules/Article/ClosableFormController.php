<?php

declare(strict_types=1);

namespace app\modules\Article;

use BackedEnum;
use DateTime;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleAuthorizationRow;
use app\modules\Article\valueObjects\ClosableFormRow;
use app\modules\Article\valueObjects\PersonNameRow;
use app\modules\Article\viewModels\ClosableFormAddViewModel;
use app\modules\Common\AbstractController;
use app\modules\Common\valueObjects\ArticleRow;
use app\modules\Common\valueObjects\ClosingVisibilityRow;

/**
 * Flow shared by the closable forms attached to an article (survey, order):
 * form edition page, creation / update, and results page.
 *
 * Access rules, identical for every form:
 * - only the creator of the article can create or modify the form;
 * - only people who can read the article can see the results, according to the
 *   visibility chosen by the creator (see canReadResults()).
 *
 * A concrete controller only describes what differs: its name, its visibility
 * vocabulary, the visibility rule, the way the results are computed and the
 * templates it renders. The templates are rendered by the concrete controllers
 * with literal paths, so that the template analysis can resolve them; the table
 * is ucfirst($formName).
 *
 * @phpstan-import-type ClosingVisibilityRowShape from ClosingVisibilityRow
 */
abstract class ClosableFormController extends AbstractController
{
    public function __construct(
        Application $application,
        protected readonly ArticleDataHelper $articleDataHelper,
        protected readonly ArticleAuthorizationService $authorizationService,
    ) {
        parent::__construct($application);
    }

    /** Lower-case name of the form: 'survey' or 'order'. */
    abstract protected function formName(): string;

    /** @return class-string<BackedEnum> */
    abstract protected function visibilityEnum(): string;

    abstract protected function defaultVisibility(): string;

    abstract protected function canReadResults(
        ClosingVisibilityRow $form,
        ArticleRow $article,
        ConnectedUser $user
    ): bool;

    /** Renders the page used to add or edit the form. */
    abstract protected function renderAddPage(ClosableFormAddViewModel $viewModel): void;

    /**
     * Builds and renders the results page.
     *
     * @param array<string, mixed> $layoutParams
     * @return bool False when the form cannot be loaded (nothing is rendered)
     */
    abstract protected function renderResultsPage(int $articleId, array $layoutParams): bool;

    public function add(int $articleId): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            return;
        }

        $article = $this->articleDataHelper->getLatestArticle([$articleId]);
        if ($article === null) {
            $this->redirect('/articles');
            return;
        }
        if (!$this->authorizationService->canEdit($article, $this->application->getConnectedUser())) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        $row = $this->dataHelper->get(
            ucfirst($this->formName()),
            ['IdArticle' => $article->id],
            'Question, Options, ClosingDate, Visibility'
        );

        $viewModel = new ClosableFormAddViewModel(
            article: $article,
            form: $row === false ? null : ClosableFormRow::fromStdClass($row),
            visibilityOptions: $this->visibilityOptions(),
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
        );

        $this->renderAddPage($viewModel);
    }

    public function createOrUpdate(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        if (!$connectedUser->isRedactor()) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->errors->methodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $schema = [
            'article_id'  => FilterInputRule::Int->value,
            'question'    => FilterInputRule::HtmlSafeText->value,
            'closingDate' => FilterInputRule::DateTime->value,
            'visibility'  => $this->application->enumToValues($this->visibilityEnum()),
            'options'     => FilterInputRule::ArrayString->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $articleId = $input['article_id'] ?? null;
        if (!is_int($articleId)) {
            $this->errors->badRequest('Missing or invalid article_id', __FILE__, __LINE__);
            return;
        }

        $article = $this->articleDataHelper->getLatestArticle([$articleId]);
        if ($article === null) {
            $this->errors->badRequest("Article {$articleId} doesn't exist", __FILE__, __LINE__);
            return;
        }
        if (!$this->authorizationService->canEdit($article, $connectedUser)) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        $question    = $input['question']    ?? '???';
        $closingDate = $input['closingDate'] ?? new DateTime('+7 days');
        $visibility  = $input['visibility']  ?? $this->defaultVisibility();

        /** @var array<string> $rawOptions */
        $rawOptions = $input['options'] ?? [];
        $options = [];
        foreach ($rawOptions as $option) {
            $options[] = str_replace('"', "''", $option);
        }

        $fields = [
            'Question'    => $question,
            'Options'     => json_encode($options),
            'ClosingDate' => $closingDate,
            'IdArticle'   => $articleId,
            'Visibility'  => $visibility,
        ];

        $table = ucfirst($this->formName());
        $existing = $this->dataHelper->get($table, ['IdArticle' => $articleId], 'Id');
        if ($existing !== false) {
            /** @var object{Id: int|string} $existing */
            $this->dataHelper->set($table, $fields, ['Id' => $existing->Id]);
        } else {
            $this->dataHelper->set($table, $fields);
        }

        $this->redirect('/article/' . $articleId);
    }

    public function viewResults(int $articleId): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $article = $this->articleDataHelper->getLatestArticle([$articleId]);
        if ($article === null) {
            $this->errors->badRequest("Article {$articleId} doesn't exist", __FILE__, __LINE__);
            return;
        }

        $connectedUser = $this->application->getConnectedUser();
        if ($connectedUser->person === null) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        // 1st gate: the results are never visible to someone who cannot read the article
        if (!$this->authorizationService->canRead(ArticleAuthorizationRow::fromArticleRow($article), $connectedUser)) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        /** @var ClosingVisibilityRowShape|false $row */
        $row = $this->dataHelper->get(ucfirst($this->formName()), ['IdArticle' => $articleId], 'Id, ClosingDate, Visibility');
        if ($row === false) {
            $this->raiseFormNotFound($articleId);
            return;
        }

        // 2nd gate: the visibility chosen by the creator of the form
        if (!$this->canReadResults(ClosingVisibilityRow::fromStdClass($row), $article, $connectedUser)) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        $rendered = $this->renderResultsPage(
            $articleId,
            $this->getAllParams([
                'page'           => $connectedUser->getPage(),
                'currentVersion' => Application::VERSION,
            ]),
        );
        if (!$rendered) {
            $this->raiseFormNotFound($articleId);
        }
    }

    /**
     * Display name of a participant, '???' when the person no longer exists.
     */
    protected function participantName(int|string $personId): string
    {
        $personData = $this->dataHelper->get('Individual', ['Id' => $personId], 'FirstName, LastName');
        if ($personData === false) {
            return '???';
        }
        /** @var object{FirstName: string, LastName: string} $personData */
        $person = PersonNameRow::fromStdClass($personData);

        return $person->firstName . ' ' . $person->lastName;
    }

    private function raiseFormNotFound(int $articleId): void
    {
        $this->errors->badRequest("No {$this->formName()} for article {$articleId}", __FILE__, __LINE__);
        $this->redirect('/article/' . $articleId);
    }

    /**
     * Translated label of each visibility, keyed by its value.
     *
     * @return array<string, string>
     */
    private function visibilityOptions(): array
    {
        $enum = $this->visibilityEnum();
        $options = [];
        foreach ($enum::cases() as $case) {
            $options[(string) $case->value] = ($this->t)("article.{$this->formName()}.visibility.{$case->value}");
        }

        return $options;
    }
}
