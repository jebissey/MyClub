<?php

declare(strict_types=1);

namespace tests\models;

use PDO;
use app\models\CarouselDataHelper;

class CarouselDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Carousel', [
            'Id',
            'Item',
            'IdArticle',
        ]);
    }

    public function testAddOrUpdateInsertsNewCarouselItem(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        /** @var CarouselDataHelper $helper */
        $helper = $this->makeHelper(CarouselDataHelper::class, $pdo);

        $result = $helper->addOrUpdate(
            ['idArticle' => 123],
            '/media/carousel/image.jpg',
        );

        $this->assertSame('Élément ajouté avec succès', $result);

        $stmt = $pdo->prepare(
            'SELECT Item, IdArticle
             FROM Carousel
             WHERE IdArticle = :idArticle',
        );
        $stmt->execute(['idArticle' => 123]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($row);
        $this->assertSame('/media/carousel/image.jpg', $row['Item']);
        $this->assertSame(123, (int) $row['IdArticle']);
    }

    public function testAddOrUpdateUpdatesExistingCarouselItem(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $pdo->prepare(
            'INSERT INTO Carousel (Item, IdArticle)
             VALUES (:item, :idArticle)',
        )->execute([
            'item' => '/media/carousel/old.jpg',
            'idArticle' => 123,
        ]);

        $id = (int) $pdo->lastInsertId();

        /** @var CarouselDataHelper $helper */
        $helper = $this->makeHelper(CarouselDataHelper::class, $pdo);

        $result = $helper->addOrUpdate(
            [
                'id' => $id,
                'idArticle' => 123,
            ],
            '/media/carousel/new.jpg',
        );

        $this->assertSame('Élément mis à jour avec succès', $result);

        $stmt = $pdo->prepare(
            'SELECT Item, IdArticle
             FROM Carousel
             WHERE Id = :id',
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($row);
        $this->assertSame('/media/carousel/new.jpg', $row['Item']);
        $this->assertSame(123, (int) $row['IdArticle']);
    }

    public function testAddOrUpdateTreatsZeroIdAsNewItem(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        /** @var CarouselDataHelper $helper */
        $helper = $this->makeHelper(CarouselDataHelper::class, $pdo);

        $result = $helper->addOrUpdate(
            [
                'id' => 0,
                'idArticle' => 456,
            ],
            '/media/carousel/image.jpg',
        );

        $this->assertSame('Élément ajouté avec succès', $result);

        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM Carousel
             WHERE IdArticle = :idArticle',
        );
        $stmt->execute(['idArticle' => 456]);

        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testGetPathsUsedInGaleryReturnsEmptyArrayForEmptyPaths(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        /** @var CarouselDataHelper $helper */
        $helper = $this->makeHelper(CarouselDataHelper::class, $pdo);

        $this->assertSame([], $helper->getPathsUsedInGalery([]));
    }

    public function testGetPathsUsedInGaleryDetectsUsedPath(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $pdo->prepare(
            'INSERT INTO Carousel (Item, IdArticle)
             VALUES (:item, :idArticle)',
        )->execute([
            'item' => '/media/2026/09/photo.jpg',
            'idArticle' => 123,
        ]);

        /** @var CarouselDataHelper $helper */
        $helper = $this->makeHelper(CarouselDataHelper::class, $pdo);

        $result = $helper->getPathsUsedInGalery([
            '/media/2026/09/photo.jpg',
        ]);

        $this->assertSame([
            '/media/2026/09/photo.jpg' => true,
        ], $result);
    }

    public function testGetPathsUsedInGaleryDetectsPathContainedInItem(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $pdo->prepare(
            'INSERT INTO Carousel (Item, IdArticle)
             VALUES (:item, :idArticle)',
        )->execute([
            'item' => '/media/2026/09/photo.jpg',
            'idArticle' => 123,
        ]);

        /** @var CarouselDataHelper $helper */
        $helper = $this->makeHelper(CarouselDataHelper::class, $pdo);

        $result = $helper->getPathsUsedInGalery([
            'photo.jpg',
        ]);

        $this->assertSame([
            'photo.jpg' => true,
        ], $result);
    }

    public function testGetPathsUsedInGaleryIgnoresUnusedPath(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $pdo->prepare(
            'INSERT INTO Carousel (Item, IdArticle)
             VALUES (:item, :idArticle)',
        )->execute([
            'item' => '/media/2026/09/photo.jpg',
            'idArticle' => 123,
        ]);

        /** @var CarouselDataHelper $helper */
        $helper = $this->makeHelper(CarouselDataHelper::class, $pdo);

        $result = $helper->getPathsUsedInGalery([
            '/media/2026/09/other.jpg',
        ]);

        $this->assertSame([], $result);
    }

    public function testGetPathsUsedInGaleryReturnsOnlyUsedPaths(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $stmt = $pdo->prepare(
            'INSERT INTO Carousel (Item, IdArticle)
             VALUES (:item, :idArticle)',
        );

        $stmt->execute([
            'item' => '/media/2026/09/photo1.jpg',
            'idArticle' => 123,
        ]);

        $stmt->execute([
            'item' => '/media/2026/09/photo2.jpg',
            'idArticle' => 124,
        ]);

        /** @var CarouselDataHelper $helper */
        $helper = $this->makeHelper(CarouselDataHelper::class, $pdo);

        $result = $helper->getPathsUsedInGalery([
            '/media/2026/09/photo1.jpg',
            '/media/2026/09/photo2.jpg',
            '/media/2026/09/photo3.jpg',
        ]);

        $this->assertSame([
            '/media/2026/09/photo1.jpg' => true,
            '/media/2026/09/photo2.jpg' => true,
        ], $result);
    }
}