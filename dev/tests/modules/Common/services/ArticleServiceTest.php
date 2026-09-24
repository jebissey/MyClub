<?php

declare(strict_types=1);

namespace tests\modules\Common\services;

use PHPUnit\Framework\TestCase;
use app\helpers\MediaManager;
use app\models\CarouselDataHelper;
use app\models\Data;
use app\modules\Common\services\ArticleService;
use app\modules\Common\valueObjects\UploadedFileInput;
use app\modules\Common\valueObjects\UploadedMedia;
use app\modules\Common\valueObjects\UploadMediaResult;
use RuntimeException;

/**
 * Double de Data (jamais DataHelper, qui est final) dont set() renvoie une
 * valeur fixe, avec capture des arguments d'appel — nommée pour rester
 * typable statiquement (PHPStan) au lieu de Data seul.
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

final class ArticleServiceTest extends TestCase
{
    private function makeDataHelperStub(int|bool $setReturn): RecordingArticleDataStub
    {
        return new RecordingArticleDataStub($setReturn);
    }

    private function makeService(
        ?CarouselDataHelper $carouselDataHelper = null,
        ?MediaManager $media = null,
        ?Data $dataHelper = null
    ): ArticleService {
        return new ArticleService(
            $carouselDataHelper ?? $this->createStub(CarouselDataHelper::class),
            $media ?? $this->createStub(MediaManager::class),
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

        $carouselDataHelper = $this->createMock(CarouselDataHelper::class);
        $carouselDataHelper->expects($this->never())->method('addOrUpdate');

        $media = $this->createMock(MediaManager::class);
        $media->expects($this->never())->method('uploadFile');

        $articleId = $this->makeService($carouselDataHelper, $media, $dataHelper)
            ->createWithMedia(42, null, 'Mon titre', 'Mon contenu');

        $this->assertSame(123, $articleId);
        $this->assertSame([
            ['Article', ['Title' => 'Mon titre', 'Content' => 'Mon contenu', 'CreatedBy' => 42], []],
        ], $dataHelper->setCalls);
    }

    public function testCreateWithMediaSuccessWithEmptyFilesArray(): void
    {
        $dataHelper = $this->makeDataHelperStub(10);

        $carouselDataHelper = $this->createMock(CarouselDataHelper::class);
        $carouselDataHelper->expects($this->never())->method('addOrUpdate');

        $articleId = $this->makeService($carouselDataHelper, null, $dataHelper)
            ->createWithMedia(7, [], 'Titre', 'Contenu');

        $this->assertSame(10, $articleId);
    }

    public function testCreateWithMediaUsesDefaultEmptyTitleAndContent(): void
    {
        $dataHelper = $this->makeDataHelperStub(5);

        $articleId = $this->makeService(null, null, $dataHelper)
            ->createWithMedia(1);

        $this->assertSame(5, $articleId);
        $this->assertSame([
            ['Article', ['Title' => '', 'Content' => '', 'CreatedBy' => 1], []],
        ], $dataHelper->setCalls);
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

        $uploadResult = new UploadMediaResult(
            file: new UploadedMedia(
                name: 'photo.jpg',
                path: '/media/img1.jpg',
                url: 'https://cdn.example.com/img1.jpg',
                size: 2048,
                type: 'image/jpeg',
            ),
        );

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
        $dataHelper = $this->makeDataHelperStub(20);

        $media = $this->createMock(MediaManager::class);
        $media->expects($this->never())->method('uploadFile');

        $carouselDataHelper = $this->createMock(CarouselDataHelper::class);
        $carouselDataHelper->expects($this->never())->method('addOrUpdate');

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
    }

    public function testCreateWithMediaHandlesMultipleFilesAndSkipsFailedOnes(): void
    {
        $dataHelper = $this->makeDataHelperStub(77);

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
                return 'ok';
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
        $dataHelper = $this->makeDataHelperStub(30);

        $media = $this->createMock(MediaManager::class);
        $media->expects($this->never())->method('uploadFile');

        $carouselDataHelper = $this->createMock(CarouselDataHelper::class);
        $carouselDataHelper->expects($this->never())->method('addOrUpdate');

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