<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\helpers\MediaManager;
use app\models\CarouselDataHelper;
use app\models\DataHelper;
use app\valueObjects\UploadedFileInput;
use RuntimeException;

class ArticleService
{
    public function __construct(
        private CarouselDataHelper $carouselDataHelper,
        private MediaManager $media,
        private DataHelper $dataHelper
    ) {
    }

    /**
     * @param array{
     *     tmp_name?: array<int, string>,
     *     error: array<int, int>,
     *     name: array<int, string>,
     *     type: array<int, string>,
     *     size: array<int, int>
     * }|null $files
     */
    public function createWithMedia(int $userId, ?array $files = null, string $title = '', string $content = ''): int
    {
        $result = $this->dataHelper->set('Article', [
            'Title'     => $title,
            'Content'   => $content,
            'CreatedBy' => $userId
        ]);

        if (!is_int($result)) {
            throw new RuntimeException('Failed to create article');
        }
        $articleId = $result;

        if (!empty($files) && isset($files['tmp_name'])) {
            foreach ($files['tmp_name'] as $index => $tmpName) {
                if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $file = new UploadedFileInput(
                    name: $files['name'][$index],
                    tmpName: $tmpName,
                    size: $files['size'][$index],
                    type: $files['type'][$index],
                );

                $upload = $this->media->uploadFile($file);

                $this->carouselDataHelper->addOrUpdate([
                    'idArticle' => $articleId
                ], $upload->file->url);
            }
        }

        return $articleId;
    }
}
