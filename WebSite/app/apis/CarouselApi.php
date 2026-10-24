<?php

declare(strict_types=1);

namespace app\apis;

use JsonException;
use Throwable;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\CarouselDataHelper;
use app\models\LanguagesDataHelper;
use app\models\PersonDataHelper;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleAuthorizationRow;

final class CarouselApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        LanguagesDataHelper $languagesDataHelper,
        PersonDataHelper $personDataHelper,
        private readonly ArticleDataHelper $articleDataHelper,
        private readonly CarouselDataHelper $carouselDataHelper,
        private readonly ArticleAuthorizationService $authorizationService,
    ) {
        parent::__construct($application, $connectedUser, $personDataHelper, $languagesDataHelper);
    }

    public function deleteItem(int $id): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isRedactor(), __FILE__, __LINE__)) {
            return;
        }

        /** @var object{IdArticle: int}|false $item */
        $item = $this->dataHelper->get('Carousel', ['Id' => $id], 'IdArticle');
        if (!$item) {
            $this->renderJsonBadRequest("Item {$id} not found", __FILE__, __LINE__);
            return;
        }

        $article = $this->articleDataHelper->getLatestArticle([$item->IdArticle]);
        if ($article === null) {
            $this->renderJsonBadRequest("Article {$item->IdArticle} not found", __FILE__, __LINE__);
            return;
        }
        // Deleting an item is an edition of the article, not a simple read
        if (!$this->authorizationService->canEdit($article, $this->application->getConnectedUser())) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        try {
            $this->dataHelper->delete('Carousel', ['Id' => $id]);
            $this->renderJsonOk(['message' => 'Élément supprimé avec succès']);
        } catch (Throwable $e) {
            $this->renderJsonError('error' . $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public function getItems(int $idArticle): void
    {
        try {
            $connectedUser = $this->application->getConnectedUser();
            if (!($connectedUser->person ?? false)) {
                $this->renderJsonForbidden(__FILE__, __LINE__);
                return;
            }

            $article = $this->articleDataHelper->getLatestArticle([$idArticle]);
            if ($article === null) {
                $this->renderJsonBadRequest("Article {$idArticle} not found", __FILE__, __LINE__);
                return;
            }
            if (!$this->authorizationService->canRead(ArticleAuthorizationRow::fromArticleRow($article), $connectedUser)) {
                $this->renderJsonForbidden(__FILE__, __LINE__);
                return;
            }

            $this->renderJsonOk(['items' => $this->dataHelper->gets(
                'Carousel',
                ['IdArticle' => $idArticle],
                'Id, IdArticle, Item'
            )]);
        } catch (QueryException $e) {
            $this->renderJsonBadRequest($e->getMessage(), $e->getFile(), $e->getLine());
        } catch (Throwable $e) {
            $this->renderJsonError('error' . $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public function saveItem(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        if ($connectedUser->person === null) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest("Invalid data", __FILE__, __LINE__);
            return;
        }
        if (!$data || !isset($data['idArticle']) || !isset($data['item'])) {
            $this->renderJsonBadRequest("Invalid data", __FILE__, __LINE__);
            return;
        }

        $idArticle = To::int($data['idArticle']);

        $article = $this->articleDataHelper->getLatestArticle([$idArticle]);
        if ($article === null) {
            $this->renderJsonBadRequest("Article {$idArticle} not found", __FILE__, __LINE__);
            return;
        }
        if (!$this->authorizationService->canEdit($article, $connectedUser)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        // An existing item can only be updated through the article it belongs to
        if (isset($data['id'])) {
            /** @var object{IdArticle: int|string}|false $existing */
            $existing = $this->dataHelper->get('Carousel', ['Id' => To::int($data['id'])], 'IdArticle');
            if ($existing !== false && (int)$existing->IdArticle !== $idArticle) {
                $this->renderJsonForbidden(__FILE__, __LINE__);
                return;
            }
        }

        $item = WebApp::sanitizeHtml(To::str($data['item']));
        try {
            /** @var array{id?: int|string, idArticle: int|string} $payload */
            $payload = ['idArticle' => $idArticle];
            if (isset($data['id'])) {
                $payload['id'] = To::int($data['id']);
            }

            $message = $this->carouselDataHelper->addOrUpdate($payload, $item);
            $this->renderJsonOk(['message' => $message]);
        } catch (Throwable $e) {
            $this->renderJsonError('error' . $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }
}
