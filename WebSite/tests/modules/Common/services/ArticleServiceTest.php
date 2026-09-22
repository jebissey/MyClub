<?php

declare(strict_types=1);

namespace tests\modules\Common\services;

use PHPUnit\Framework\TestCase;
use app\helpers\MediaManager;
use app\models\CarouselDataHelper;
use app\models\DataHelper;
use app\modules\Common\services\ArticleService;
use app\modules\Common\valueObjects\UploadedFileInput;
use RuntimeException;

final class ArticleServiceTest extends TestCase
{
    private function makeService(
        ?CarouselDataHelper $carouselDataHelper = null,
        ?MediaManager $media = null,
        ?DataHelper $dataHelper = null
    ): ArticleService {
        return new ArticleService(
            $carouselDataHelper ?? $this->createStub(CarouselDataHelper::class),
            $media ?? $this->createStub(MediaManager::class),
            $dataHelper ?? $this->createStub(DataHelper::class)
        );
    }

    /**
     * @return array{
     *     tmp_name: array<int, string>,
     *     error: array<int, int>,
     *     name: array<int, string>,
     *     type: array<int, string>,
     *     size: array<int, int>
     * }
     */
    private function makeFiles(array $files): array
    {
        $result = [
            'tmp_name' => [],
            'error'    => [],
            'name'     => [],
            'type'     => [],
            'size'     => [],
        ];

        foreach ($files as $index => $file) {
            $result['tmp_name'][$index] = $file['tmp_name'] ?? '/tmp/phpXXXXXX';
            $result['error'][$index]    = $file['error'] ?? UPLOAD_ERR_OK;
            $result['name'][$index]     = $file['name'] ?? 'file.jpg';
            $result['type'][$index]     = $file['type'] ?? 'image/jpeg';
            $result['size'][$index]     = $file['size'] ?? 1024;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // createWithMedia – success without files
    // -------------------------------------------------------------------------

    public function testCreateWithMediaSuccessWithoutFiles(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->once())
            ->method('set')
            ->with('Article', [
                'Title'     => 'Mon titre',
                'Content'   => 'Mon contenu',
                'CreatedBy' => 42,
            ])
            ->willReturn(123);

        $carouselDataHelper = $this->createMock(CarouselDataHelper::class);
        $carouselDataHelper->expects($this->never())->method('addOrUpdate');

        $media = $this->createMock(MediaManager::class);
        $media->expects($this->never())->method('uploadFile');

        $articleId = $this->makeService($carouselDataHelper, $media, $dataHelper)
            ->createWithMedia(42, null, 'Mon titre', 'Mon contenu');

        $this->assertSame(123, $articleId);
    }

    public function testCreateWithMediaSuccessWithEmptyFilesArray(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('set')->willReturn(10);

        $carouselDataHelper = $this->createMock(CarouselDataHelper::class);
        $carouselDataHelper->expects($this->never())->method('addOrUpdate');

        $articleId = $this->makeService($carouselDataHelper, null, $dataHelper)
            ->createWithMedia(7, [], 'Titre', 'Contenu');

        $this->assertSame(10, $articleId);
    }

    public function testCreateWithMediaUsesDefaultEmptyTitleAndContent(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->once())
            ->method('set')
            ->with('Article', [
                'Title'     => '',
                'Content'   => '',
                'CreatedBy' => 1,
            ])
            ->willReturn(5);

        $articleId = $this->makeService(null, null, $dataHelper)
            ->createWithMedia(1);

        $this->assertSame(5, $articleId);
    }

    // -------------------------------------------------------------------------
    // createWithMedia – failure on article creation
    // -------------------------------------------------------------------------

    public function testCreateWithMediaThrowsWhenDataHelperDoesNotReturnInt(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('set')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create article');

        $this->makeService(null, null, $dataHelper)
            ->createWithMedia(42, null, 'Titre', 'Contenu');
    }

    public function testCreateWithMediaThrowsWhenDataHelperReturnsString(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('set')->willReturn('error');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create article');

        $this->makeService(null, null, $dataHelper)
            ->createWithMedia(42);
    }

    // -------------------------------------------------------------------------
    // createWithMedia – with files
    // -------------------------------------------------------------------------

    public function testCreateWithMediaUploadsValidFilesAndAddsToCarousel(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('set')->willReturn(55);

        $uploadResult = (object) [
            'file' => (object) ['url' => 'https://cdn.example.com/img1.jpg'],
        ];

        $media = $this->createMock(MediaManager::class);
        $media->expects($this->once())
            ->method('uploadFile')
            ->with($this->callback(function (UploadedFileInput $file) {
                return $file->name === 'photo.jpg'
                    && $file->tmpName === '/tmp/phpABC'
                    && $file->size === 2048
                    && $file->type === 'image/jpeg';
            }))
            ->willReturn($uploadResult);

        $carouselDataHelper = $this->createMock(CarouselDataHelper::class);
        $carouselDataHelper->expects($this->once())
            ->method('addOrUpdate')
            ->with(
                ['idArticle' => 55],
                'https://cdn.example.com/img1.jpg'
            );

        $files = $this->makeFiles([
            [
                'tmp_name' => '/tmp/phpABC',
                'error'    => UPLOAD_ERR_OK,
                'name'     => 'photo.jpg',
                'type'     => 'image/jpeg',
                'size'     => 2048,
            ],
        ]);

        $articleId = $this->makeService($carouselDataHelper, $media, $dataHelper)
            ->createWithMedia(42, $files, 'Titre', 'Contenu');

        $this->assertSame(55, $articleId);
    }

    public function testCreateWithMediaSkipsFilesWithUploadError(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('set')->willReturn(20);

        $media = $this->createMock(MediaManager::class);
        $media->expects($this->never())->method('uploadFile');

        $carouselDataHelper = $this->createMock(CarouselDataHelper::class);
        $carouselDataHelper->expects($this->never())->method('addOrUpdate');

        $files = $this->makeFiles([
            [
                'tmp_name' => '/tmp/phpERR',
                'error'    => UPLOAD_ERR_INI_SIZE, // or any non-OK
                'name'     => 'too-big.jpg',
            ],
        ]);

        $articleId = $this->makeService($carouselDataHelper, $media, $dataHelper)
            ->createWithMedia(1, $files);

        $this->assertSame(20, $articleId);
    }

    public function testCreateWithMediaHandlesMultipleFilesAndSkipsFailedOnes(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('set')->willReturn(77);

        $uploadResult1 = (object) [
            'file' => (object) ['url' => 'https://cdn.example.com/a.jpg'],
        ];
        $uploadResult2 = (object) [
            'file' => (object) ['url' => 'https://cdn.example.com/b.png'],
        ];

        $media = $this->createMock(MediaManager::class);
        $media->expects($this->exactly(2))
            ->method('uploadFile')
            ->willReturnOnConsecutiveCalls($uploadResult1, $uploadResult2);

        $carouselDataHelper = $this->createMock(CarouselDataHelper::class);

        $expectedCalls = [
            [['idArticle' => 77], 'https://cdn.example.com/a.jpg'],
            [['idArticle' => 77], 'https://cdn.example.com/b.png'],
        ];
        $callIndex = 0;

        $carouselDataHelper->expects($this->exactly(2))
            ->method('addOrUpdate')
            ->willReturnCallback(function (...$args) use (&$callIndex, $expectedCalls) {
                $this->assertSame($expectedCalls[$callIndex], $args);
                $callIndex++;
            });

        $files = $this->makeFiles([
            [
                'tmp_name' => '/tmp/ok1',
                'error'    => UPLOAD_ERR_OK,
                'name'     => 'a.jpg',
                'type'     => 'image/jpeg',
                'size'     => 100,
            ],
            [
                'tmp_name' => '/tmp/fail',
                'error'    => UPLOAD_ERR_PARTIAL,
                'name'     => 'fail.jpg',
            ],
            [
                'tmp_name' => '/tmp/ok2',
                'error'    => UPLOAD_ERR_OK,
                'name'     => 'b.png',
                'type'     => 'image/png',
                'size'     => 200,
            ],
        ]);

        $articleId = $this->makeService($carouselDataHelper, $media, $dataHelper)
            ->createWithMedia(99, $files, 'Multi', 'Test');

        $this->assertSame(77, $articleId);
    }

    public function testCreateWithMediaDoesNothingWhenFilesHasNoTmpNameKey(): void
    {
        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('set')->willReturn(30);

        $media = $this->createMock(MediaManager::class);
        $media->expects($this->never())->method('uploadFile');

        $carouselDataHelper = $this->createMock(CarouselDataHelper::class);
        $carouselDataHelper->expects($this->never())->method('addOrUpdate');

        // Structure invalide (pas de clé tmp_name)
        $files = [
            'error' => [UPLOAD_ERR_OK],
            'name'  => ['x.jpg'],
            'type'  => ['image/jpeg'],
            'size'  => [100],
        ];

        $articleId = $this->makeService($carouselDataHelper, $media, $dataHelper)
            ->createWithMedia(1, $files);

        $this->assertSame(30, $articleId);
    }
}
