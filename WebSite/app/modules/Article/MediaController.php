<?php

declare(strict_types=1);

namespace app\modules\Article;

use app\helpers\Application;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\MessageDataHelper;
use app\models\PersonGroupDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Article\services\MediaCatalog;
use app\modules\Article\services\MediaFileListBuilder;
use app\modules\Article\services\MediaFileStreamer;
use app\modules\Article\valueObjects\MediaListCriteria;
use app\modules\Article\valueObjects\SharedFileRow;
use app\modules\Article\viewModels\MediaListViewModel;
use app\modules\Article\viewModels\MediaUploadViewModel;
use app\modules\Article\viewModels\MediaUsesInArticlesViewModel;
use app\modules\Article\viewModels\MediaUsesInMessagesViewModel;
use app\modules\Common\valueObjects\Person;
use app\modules\Common\viewModels\PageViewModel;

/**
 * @phpstan-import-type Candidate from MediaCatalog
 * @phpstan-type UsageMap array{
 *     article: array<string, bool>,
 *     gallery: array<string, bool>,
 *     shared: array<string, bool>,
 *     message: array<string, bool>
 * }
 * @phpstan-type FileRow array{
 *     name: string,
 *     path: string,
 *     url: string,
 *     urlRedactor: string,
 *     size: int,
 *     date: string,
 *     month: string,
 *     inGalery: bool,
 *     inArticle: bool,
 *     shared: bool,
 *     inMessage: bool
 * }
 */
final class MediaController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly MediaCatalog $catalog,
        private readonly MediaFileListBuilder $fileListBuilder,
        private readonly MediaFileStreamer $fileStreamer,
        private readonly ArticleDataHelper $articleDataHelper,
        private readonly PersonGroupDataHelper $personGroupDataHelper,
        private readonly MessageDataHelper $messageDataHelper,
    ) {
        parent::__construct($application);
    }

    public function getSharedFile(string $token): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        /**
         * @var object{
         *     Id: int|string,
         *     IdGroup: int|string|null,
         *     OnlyForMembers: int|string,
         *     Item: string
         * }|false $row
         */
        $row = $this->dataHelper->get(
            'SharedFile',
            ['Token' => $token],
            'Id, IdGroup, OnlyForMembers, Item'
        );

        if (!$row) {
            $this->errors->badRequest('Token unknown', __FILE__, __LINE__);
            return;
        }

        $sharedFile = SharedFileRow::fromStdClass($row);

        if (
            !$this->canAccessSharedFile(
                $sharedFile->onlyForMembers !== 0,
                $sharedFile->idGroup,
                $this->application->getConnectedUser()->person
            )
        ) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        $this->download($sharedFile->item);
    }

    public function gpxViewer(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $viewModel = new PageViewModel(
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
        );
        $this->views->render('Article/views/media_gpxViewer.latte', $viewModel);
    }

    public function help(): void
    {
        $this->renderHelp('Help_Media_list', fn($u) => $u->isRedactor(), __FILE__, __LINE__);
    }

    public function listFiles(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            return;
        }

        $criteria = MediaListCriteria::fromQuery($this->flight->request()->query);

        $months = $this->catalog->months($criteria->year);
        if (!in_array($criteria->month, $months, true)) {
            $criteria = $criteria->withMonth('');
        }

        $filteredFiles = $this->fileListBuilder->build($criteria);
        $totalCount = count($this->catalog->findCandidates($criteria->withoutFilters()));
        $connectedUser = $this->application->getConnectedUser();

        $viewModel = new MediaListViewModel(
            files: $filteredFiles,
            filteredCount: count($filteredFiles),
            totalCount: $totalCount,
            years: $this->catalog->years(),
            currentYear: $criteria->year,
            months: $months,
            currentMonth: $criteria->month,
            fileExtensions: $this->catalog->extensions(),
            currentFileExtension: $criteria->fileExtension,
            search: $criteria->search,
            unusedOnly: $criteria->unusedOnly,
            baseUrl: WebApp::getBaseUrl(),
            groups: $this->getActiveGroups(),
            layoutParams: $this->getAllParams([
                'page' => $connectedUser->getPage(),
                'isEditor' => $connectedUser->isEditor(),
            ]),
        );
        $this->views->render('Article/views/media_index.latte', $viewModel);
    }

    public function serveFile(string $year, string $month, string $filename): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $connectedUser = $this->application->getConnectedUser();
        $canRead = $this->authorizationDataHelper->personCanReadMediaFile(
            (int)$year,
            (int)$month,
            $filename,
            $connectedUser,
            $this->getNavItems($connectedUser->person?->email)
        );

        if (!$canRead) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        $this->viewFile((int)$year, (int)$month, $filename);
    }

    public function showUploadForm(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            $viewModel = new MediaUploadViewModel(
                i18n: [
                    'uploadInProgress' => $this->languagesDataHelper->translate('loading'),
                    'uploadSuccess'    => $this->languagesDataHelper->translate('media.upload.success_title'),
                    'uploadError'      => $this->languagesDataHelper->translate('media.upload.error'),
                ],
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );
            $this->views->render('Article/views/media_upload.latte', $viewModel);
        }
    }

    public function showUsesInArticles(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            $rawPath = $this->flight->request()->query->path ?? null;
            $path = is_string($rawPath) ? $rawPath : '';

            $viewModel = new MediaUsesInArticlesViewModel(
                path: $path,
                articles: $path !== '' ? $this->articleDataHelper->inArticles($path) : [],
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );
            $this->views->render('Article/views/media_uses_articles.latte', $viewModel);
        }
    }

    public function showUsesInMessages(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            $rawPath = $this->flight->request()->query->path ?? null;
            $path = is_string($rawPath) ? $rawPath : '';

            $uses = $path !== ''
                ? $this->messageDataHelper->getMessageUses($path)
                : ['events' => [], 'articles' => [], 'groups' => []];

            $viewModel = new MediaUsesInMessagesViewModel(
                path: $path,
                events: $uses['events'],
                articles: $uses['articles'],
                groups: $uses['groups'],
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );
            $this->views->render('Article/views/media_uses_messages.latte', $viewModel);
        }
    }

    public function viewFileForRedactor(string $year, string $month, string $filename): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            $this->viewFile((int)$year, (int)$month, $filename);
        }
    }

    #region Private functions
    private function viewFile(int $year, int $month, string $filename): void
    {
        $realPath = $this->fileStreamer->locateByDate($year, $month, $filename);
        if ($realPath === null) {
            $this->errors->badRequest('File not found', __FILE__, __LINE__);
            return;
        }
        $this->fileStreamer->send($realPath, false);
    }

    /**
     * A shared file is accessible when the visitor satisfies both restrictions that apply to it:
     * being a member (if required) and belonging to the target group (if any).
     */
    private function canAccessSharedFile(bool $membersOnly, ?int $idGroup, ?Person $person): bool
    {
        if ($membersOnly && $person === null) {
            return false;
        }

        if ($idGroup === null) {
            return true;
        }

        return $person !== null
            && $this->personGroupDataHelper->isPersonInGroup($person->id, $idGroup);
    }

    private function download(string $file): void
    {
        $realPath = $this->fileStreamer->locate($file);
        if ($realPath === null) {
            $this->errors->badRequest('File unknown', __FILE__, __LINE__);
            return;
        }
        $this->fileStreamer->send($realPath, true);
    }
}
