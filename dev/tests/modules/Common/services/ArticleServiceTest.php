<?php

declare(strict_types=1);

namespace tests\modules\Common\services;

use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use app\helpers\interfaces\MediaManagerInterface;
use app\models\CarouselDataHelper;
use app\models\Data;
use app\modules\Common\services\ArticleService;
use app\modules\Common\valueObjects\UploadedFileInput;
use app\modules\Common\valueObjects\UploadedFilesInput;
use app\modules\Common\valueObjects\UploadedMedia;
use app\modules\Common\valueObjects\UploadMediaResult;

/**
 * Test double of Data (never DataHelper, which is final): set() returns a fixed
 * value and the call arguments are recorded.
 */
final class RecordingArticleDataStub extends Data
{
    /** @var list<array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}> */
    public array $setCalls = [];

    public function __construct(private int|bool $setReturn = false) {}

    public function set(string $table, array $fields, array $where = []): int|bool
    {
        $this->setCalls[] = [$table, $fields, $where];

        return $this->setReturn;
    }
}

final class ArticleServiceTest extends TestCase
{
    private PDO $carouselPdo;

    protected function setUp(): void
    {
        $this->carouselPdo = new PDO('sqlite::memory:');
        $this->carouselPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->carouselPdo->exec(
            'CREATE TABLE Carousel (Id INTEGER PRIMARY KEY AUTOINCREMENT, IdArticle INTEGER, Item TEXT)'
        );
    }

    private function makeDataHelperStub(int|bool $setReturn): RecordingArticleDataStub
    {
        return new RecordingArticleDataStub($setReturn);
    }

    /**
     * CarouselDataHelper is final and needs a full Application: its constructor is
     * bypassed and only the inherited $pdo property is set.
     */
    private function makeCarouselHelper(): CarouselDataHelper
    {
        /** @var CarouselDataHelper $helper */
        $helper = (new ReflectionClass(CarouselDataHelper::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(Data::class, 'pdo'))->setValue($helper, $this->carouselPdo);

        // Data's constructor normally loads the list of tables; it is bypassed here
        $tables = $this->carouselPdo->query("SELECT name FROM sqlite_master WHERE type = 'table'");
        $this->assertNotFalse($tables);
        (new ReflectionProperty(Data::class, 'tables'))->setValue($helper, $tables->fetchAll(PDO::FETCH_COLUMN));

        return $helper;
    }

    /**
     * @return list<array{IdArticle: int, Item: string}>
     */
    private function carouselRows(): array
    {
        $statement = $this->carouselPdo->query('SELECT IdArticle, Item FROM Carousel ORDER BY Id');
        $this->assertNotFalse($statement);

        $rows = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = ['IdArticle' => (int)$row['IdArticle'], 'Item' => (string)$row['Item']];
        }

        return $rows;
    }

    private function makeService(
        ?CarouselDataHelper $carouselDataHelper = null,
        ?MediaManagerInterface $media = null,
        ?Data $dataHelper = null
    ): ArticleService {
        return new ArticleService(
            $carouselDataHelper ?? $this->makeCarouselHelper(),
            $media ?? $this->createStub(MediaManagerInterface::class),
            $dataHelper ?? $this->makeDataHelperStub(false)
        );
    }

    /**
     * Builds the files the way PHP exposes a multi-upload field, then converts them
     * with the production factory.
     *
     * @param list<array{
     *     tmp_name?: string,
     *     error?: int,
     *     name?: string,
     *     type?: string,
     *     size?: int
     * }> $files
     */
    private function makeFiles(array $files): UploadedFilesInput
    {
        $result = [
            'tmp_name' => [],
            'error' => [],
            'name' => [],
            'type' => [],
            'size' => [],
        ];

        foreach ($files as $index => $file) {
            $result['tmp_name'][$index] = $file['tmp_name'] ?? '/tmp/phpXXXXXX';
            $result['error'][$index] = $file['error'] ?? UPLOAD_ERR_OK;
            $result['name'][$index] = $file['name'] ?? 'file.jpg';
            $result['type'][$index] = $file['type'] ?? 'image/jpeg';
            $result['size'][$index] = $file['size'] ?? 1024;
        }

        return UploadedFilesInput::fromPhpFiles($result);
    }

    // -------------------------------------------------------------------------
    // createWithMedia – success without files
    // -------------------------------------------------------------------------

    public function testCreateWithMediaSuccessWithoutFiles(): void
    {
        $dataHelper = $this->makeDataHelperStub(123);

        $media = $this->createMock(MediaManagerInterface::class);
        $media->expects($this->never())->method('uploadFile');

        $articleId = $this->makeService(null, $media, $dataHelper)
            ->createWithMedia(42, null, 'Mon titre', 'Mon contenu');

        $this->assertSame(123, $articleId);
        $this->assertSame([
            [
                'Article',
                [
                    'Title' => 'Mon titre',
                    'Content' => 'Mon contenu',
                    'CreatedBy' => 42,
                ],
                [],
            ],
        ], $dataHelper->setCalls);
        $this->assertSame([], $this->carouselRows());
    }

    public function testCreateWithMediaSuccessWithEmptyFilesArray(): void
    {
        $dataHelper = $this->makeDataHelperStub(10);

        $articleId = $this->makeService(null, null, $dataHelper)
            ->createWithMedia(
                7,
                $this->makeFiles([]),
                'Titre',
                'Contenu'
            );

        $this->assertSame(10, $articleId);
        $this->assertSame([], $this->carouselRows());
    }

    public function testCreateWithMediaUsesDefaultEmptyTitleAndContent(): void
    {
        $dataHelper = $this->makeDataHelperStub(5);

        $articleId = $this->makeService(null, null, $dataHelper)
            ->createWithMedia(1);

        $this->assertSame(5, $articleId);
        $this->assertSame([
            [
                'Article',
                [
                    'Title' => '',
                    'Content' => '',
                    'CreatedBy' => 1,
                ],
                [],
            ],
        ], $dataHelper->setCalls);
        $this->assertSame([], $this->carouselRows());
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

        $media = $this->createMock(MediaManagerInterface::class);
        $media->expects($this->once())
            ->method('uploadFile')
            ->with($this->callback(function (UploadedFileInput $file): bool {
                return $file->name === 'photo.jpg'
                    && $file->tmpName === '/tmp/phpABC'
                    && $file->size === 2048
                    && $file->type === 'image/jpeg';
            }))
            ->willReturn($uploadResult);

        $files = $this->makeFiles([
            [
                'tmp_name' => '/tmp/phpABC',
                'error' => UPLOAD_ERR_OK,
                'name' => 'photo.jpg',
                'type' => 'image/jpeg',
                'size' => 2048,
            ],
        ]);

        $articleId = $this->makeService(null, $media, $dataHelper)
            ->createWithMedia(42, $files, 'Titre', 'Contenu');

        $this->assertSame(55, $articleId);
        $this->assertSame([
            ['IdArticle' => 55, 'Item' => 'https://cdn.example.com/img1.jpg'],
        ], $this->carouselRows());
    }

    public function testCreateWithMediaSkipsFilesWithUploadError(): void
    {
        $dataHelper = $this->makeDataHelperStub(20);

        $media = $this->createMock(MediaManagerInterface::class);
        $media->expects($this->never())->method('uploadFile');

        $files = $this->makeFiles([
            [
                'tmp_name' => '/tmp/phpERR',
                'error' => UPLOAD_ERR_INI_SIZE,
                'name' => 'too-big.jpg',
            ],
        ]);

        $articleId = $this->makeService(null, $media, $dataHelper)
            ->createWithMedia(1, $files);

        $this->assertSame(20, $articleId);
        $this->assertSame([], $this->carouselRows());
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

        $media = $this->createMock(MediaManagerInterface::class);
        $media->expects($this->exactly(2))
            ->method('uploadFile')
            ->willReturnOnConsecutiveCalls($uploadResult1, $uploadResult2);

        $files = $this->makeFiles([
            [
                'tmp_name' => '/tmp/ok1',
                'error' => UPLOAD_ERR_OK,
                'name' => 'a.jpg',
                'type' => 'image/jpeg',
                'size' => 100,
            ],
            [
                'tmp_name' => '/tmp/fail',
                'error' => UPLOAD_ERR_PARTIAL,
                'name' => 'fail.jpg',
            ],
            [
                'tmp_name' => '/tmp/ok2',
                'error' => UPLOAD_ERR_OK,
                'name' => 'b.png',
                'type' => 'image/png',
                'size' => 200,
            ],
        ]);

        $articleId = $this->makeService(null, $media, $dataHelper)
            ->createWithMedia(99, $files, 'Multi', 'Test');

        $this->assertSame(77, $articleId);
        $this->assertSame([
            ['IdArticle' => 77, 'Item' => 'https://cdn.example.com/a.jpg'],
            ['IdArticle' => 77, 'Item' => 'https://cdn.example.com/b.png'],
        ], $this->carouselRows());
    }

    public function testCreateWithMediaDoesNothingWhenTmpNameListIsEmpty(): void
    {
        $dataHelper = $this->makeDataHelperStub(30);

        $media = $this->createMock(MediaManagerInterface::class);
        $media->expects($this->never())->method('uploadFile');

        $files = UploadedFilesInput::fromPhpFiles([
            'tmp_name' => [],
            'error' => [UPLOAD_ERR_OK],
            'name' => ['x.jpg'],
            'type' => ['image/jpeg'],
            'size' => [100],
        ]);

        $articleId = $this->makeService(null, $media, $dataHelper)
            ->createWithMedia(1, $files);

        $this->assertSame(30, $articleId);
        $this->assertSame([], $this->carouselRows());
    }
}
