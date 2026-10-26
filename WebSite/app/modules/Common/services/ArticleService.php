<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use RuntimeException;
use app\helpers\interfaces\MediaManagerInterface;
use app\models\CarouselDataHelper;
use app\models\Data;
use app\modules\Common\valueObjects\UploadedFilesInput;

final readonly class ArticleService
{
    public function __construct(
        private CarouselDataHelper $carouselDataHelper,
        private MediaManagerInterface $media,
        private Data $dataHelper
    ) {
    }

    public function createWithMedia(
        int $userId,
        ?UploadedFilesInput $files = null,
        string $title = '',
        string $content = ''
    ): int {
        $result = $this->dataHelper->set('Article', [
            'Title'     => $title,
            'Content'   => $content,
            'CreatedBy' => $userId
        ]);

        if (!is_int($result)) {
            throw new RuntimeException('Failed to create article');
        }
        $articleId = $result;

        foreach ($files->files ?? [] as $file) {
            $upload = $this->media->uploadFile($file);

            $this->carouselDataHelper->addOrUpdate([
                'idArticle' => $articleId
            ], $upload->file->url);
        }

        return $articleId;
    }
}
