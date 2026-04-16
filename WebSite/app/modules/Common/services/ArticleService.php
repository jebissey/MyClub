<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\helpers\MediaManager;
use app\models\CarouselDataHelper;
use app\models\DataHelper;

class ArticleService
{
    public function __construct(
        private CarouselDataHelper $carouselDataHelper,
        private MediaManager $media,
        private DataHelper $dataHelper
    ) {}

    public function createWithMedia(int $userId, ?array $files = null, string $title = '', string $content = ''): int
    {
        $articleId = $this->dataHelper->set('Article', [
            'Title'     => $title,
            'Content'   => $content,
            'CreatedBy' => $userId
        ]);

        if (!empty($files) && isset($files['tmp_name']) && is_array($files['tmp_name'])) {
            foreach ($files['tmp_name'] as $index => $tmpName) {

                if ($files['error'][$index] !== UPLOAD_ERR_OK) continue;

                $file = [
                    'name'     => $files['name'][$index],
                    'type'     => $files['type'][$index],
                    'tmp_name' => $tmpName,
                    'size'     => $files['size'][$index],
                    'error'    => $files['error'][$index],
                ];

                $upload = $this->media->uploadFile($file);

                $this->carouselDataHelper->set_([
                    'idArticle' => $articleId
                ], $upload['file']['url']);
            }
        }

        return $articleId;
    }
}
