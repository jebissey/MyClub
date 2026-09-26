<?php
// tests/modules/Common/services/ArticleServiceTest.php

declare(strict_types=1);

namespace tests\modules\Common\services;

use PHPUnit\Framework\TestCase;
use app\helpers\interfaces\MediaManagerInterface;
use app\models\interfaces\CarouselDataHelperInterface;
use app\models\Data;
use app\modules\Common\services\ArticleService;
use app\modules\Common\valueObjects\UploadedFileInput;
use app\modules\Common\valueObjects\UploadedMedia;
use app\modules\Common\valueObjects\UploadMediaResult;
use RuntimeException;

/**
 * Double de Data (jamais DataHelper, qui est final) dont set() renvoie une
 * valeur fixe, avec capture des arguments d'appel.
 */
final class RecordingArticleDataStub extends Data
{
    /** @var list<array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}> */
    public array $setCalls = [];

    public function __construct(private int|bool $setReturn = false)
    {
    }

    public function set(string $table, array $fields, array $where = []): int|bool
    {
        $this->setCalls[] = [$table, $fields, $where];
        return $this->setReturn;
    }
}

/**
 * Stub de CarouselDataHelperInterface qui enregistre les appels à addOrUpdate.
 */
final class RecordingCarouselDataHelperStub implements CarouselDataHelperInterface
{
    /** @var list<array{0: array<string, mixed>, 1: string}> */
    public array $addOrUpdateCalls = [];

    public function addOrUpdate(array $data, string $item): string
    {
        $this->addOrUpdateCalls[] = [$data, $item];
        return 'ok';
    }
}

final class ArticleServiceTest extends TestCase
{
    private function makeDataHelperStub(int|bool $setReturn): RecordingArticleDataStub
    {
        return new RecordingArticleDataStub($setReturn);
    }

    private function makeCarouselStub(): RecordingCarouselDataHelperStub
    {
        return new RecordingCarouselDataHelperStub();
    }

    private function makeService(
        ?CarouselDataHelperInterface $carouselDataHelper = null,
        ?MediaManagerInterface $media = null,
        ?Data $dataHelper = null
    ): ArticleService {
        return new ArticleService(
            $carouselDataHelper ?? $this->makeCarouselStub(),
            $media ?? $this->createStub(MediaManagerInterface::class),
            $dataHelper ?? $this->makeDataHelperStub(false)
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
        $dataHelper = $this->makeDataHelperStub(123);
        $carouselDataHelper = $this->makeCarouselStub();

        $media = $this->createMock(MediaManagerInterface::class);
        $media->expects($this->never())->method('uploadFile');

        $articleId = $this->makeService($carouselDataHelper, $media, $dataHelper)
            ->createWithMedia(42, null, 'Mon titre', 'Mon contenu');

        $this->assertSame(123, $articleId);
        $this->assertSame([
            ['Article', ['Title' => 'Mon titre', 'Content' => 'Mon contenu', 'CreatedBy' => 42], []],
        ], $dataHelper->setCalls);
        $this->assertSame([], $carouselDataHelper->addOrUpdateCalls);
    }

    public function testCreateWithMediaSuccessWithEmptyFilesArray(): void
    {
        $dataHelper = $this->makeDataHelperStub(10);
        $carouselDataHelper = $this->makeCarouselStub();

        $articleId = $this->makeService($carouselDataHelper, null, $dataHelper)
            ->createWithMedia(7, [], 'Titre', 'Contenu');

        $this->assertSame(10, $articleId);
        $this->assertSame([], $carouselDataHelper->addOrUpdateCalls);
    }

    public function testCreateWithMediaUsesDefaultEmptyTitleAndContent(): void
    {
        $dataHelper = $this->makeDataHelperStub(5);
        $carouselDataHelper = $this->makeCarouselStub();

        $articleId = $this->makeService($carouselDataHelper, null, $dataHelper)
            ->createWithMedia(1);

        $this->assertSame(5, $articleId);
        $this->assertSame([
            ['Article', ['Title' => '', 'Content' => '', 'CreatedBy' => 1], []],
        ], $dataHelper->setCalls);
        $this->assertSame([], $carouselDataHelper->addOrUpdateCalls);
    }

    // -------------------------------------------------------------------------
    // createWithMedia – failure on article creation
    // -------------------------------------------------------------------------

    public function testCreateWithMediaThrowsWhenDataHelperDoesNotReturnInt(): void
    {
        $dataHelper = $this->makeDataHelperStub(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create article');

        $this->makeService(null, null, $dataHelper)
            ->createWithMedia(42, null, 'Titre', 'Contenu');
    }

    // -------------------------------------------------------------------------
    // createWithMedia – with files
    // -------------------------------------------------------------------------

    public function testCreateWithMediaUploadsValidFilesAndAddsToCarousel(): void
    {
        $dataHelper = $this->makeDataHelperStub(55);
        $carouselDataHelper = $this->makeCarouselStub();

        $uploadResult = new UploadMediaResult(
            file: new UploadedMedia(
                name: 'photo.jpg',
                path: '/media/img1.jpg',
                url: 'https://cdn.example.com/img1.jpg',
                size: 2048,
                type: 'image/jpeg',
            ),
        );

        $media = $this->createMock(MediaManagerInterface::class);
        $media->expects($this->once())
            ->method('uploadFile')
            ->with($this->callback(function (UploadedFileInput $file) {
                return $file->name === 'photo.jpg'
                    && $file->tmpName === '/tmp/phpABC'
                    && $file->size === 2048
                    && $file->type === 'image/jpeg';
            }))
            ->willReturn($uploadResult);

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
        $this->assertSame([
            [['idArticle' => 55], 'https://cdn.example.com/img1.jpg'],
        ], $carouselDataHelper->addOrUpdateCalls);
    }

    public function testCreateWithMediaSkipsFilesWithUploadError(): void
    {
        $dataHelper = $this->makeDataHelperStub(20);
        $carouselDataHelper = $this->makeCarouselStub();

        $media = $this->createMock(MediaManagerInterface::class);
        $media->expects($this->never())->method('uploadFile');

        $files = $this->makeFiles([
            [
                'tmp_name' => '/tmp/phpERR',
                'error'    => UPLOAD_ERR_INI_SIZE,
                'name'     => 'too-big.jpg',
            ],
        ]);

        $articleId = $this->makeService($carouselDataHelper, $media, $dataHelper)
            ->createWithMedia(1, $files);

        $this->assertSame(20, $articleId);
        $this->assertSame([], $carouselDataHelper->addOrUpdateCalls);
    }

    public function testCreateWithMediaHandlesMultipleFilesAndSkipsFailedOnes(): void
    {
        $dataHelper = $this->makeDataHelperStub(77);
        $carouselDataHelper = $this->makeCarouselStub();

        $uploadResult1 = new UploadMediaResult(
            file: new UploadedMedia(
                name: 'a.jpg',
                path: '/media/a.jpg',
                url: 'https://cdn.example.com/a.jpg',
                size: 100,
                type: 'image/jpeg',
            ),
        );
        $uploadResult2 = new UploadMediaResult(
            file: new UploadedMedia(
                name: 'b.png',
                path: '/media/b.png',
                url: 'https://cdn.example.com/b.png',
                size: 200,
                type: 'image/png',
            ),
        );

        $media = $this->createMock(MediaManagerInterface::class);
        $media->expects($this->exactly(2))
            ->method('uploadFile')
            ->willReturnOnConsecutiveCalls($uploadResult1, $uploadResult2);

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
        $this->assertSame([
            [['idArticle' => 77], 'https://cdn.example.com/a.jpg'],
            [['idArticle' => 77], 'https://cdn.example.com/b.png'],
        ], $carouselDataHelper->addOrUpdateCalls);
    }

    public function testCreateWithMediaDoesNothingWhenFilesHasNoTmpNameKey(): void
    {
        $dataHelper = $this->makeDataHelperStub(30);
        $carouselDataHelper = $this->makeCarouselStub();

        $media = $this->createMock(MediaManagerInterface::class);
        $media->expects($this->never())->method('uploadFile');

        $files = [
            'error' => [UPLOAD_ERR_OK],
            'name'  => ['x.jpg'],
            'type'  => ['image/jpeg'],
            'size'  => [100],
        ];

        $articleId = $this->makeService($carouselDataHelper, $media, $dataHelper)
            ->createWithMedia(1, $files);

        $this->assertSame(30, $articleId);
        $this->assertSame([], $carouselDataHelper->addOrUpdateCalls);
    }
}