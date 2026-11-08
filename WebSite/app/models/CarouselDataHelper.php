<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use RuntimeException;
use app\helpers\Application;
use app\modules\Common\valueObjects\CarouselItemRow;

final class CarouselDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * @param array{id?: int|string, idArticle: int|string} $data
     */
    public function addOrUpdate(array $data, string $item): string
    {
        if (!empty($data['id'])) {
            $this->set('Carousel', ['Item' => $item], [
                'Id' => $data['id'],
                'IdArticle' => $data['idArticle']
            ]);
            return 'Élément mis à jour avec succès';
        } else {
            $this->set('Carousel', [
                'Item' => $item,
                'IdArticle' => $data['idArticle']
            ]);
            return 'Élément ajouté avec succès';
        }
    }


    /** @return list<CarouselItemRow> */
    public function getCarouselItems(int $articleId): array
    {
        $rows = $this->gets('Carousel', ['IdArticle' => $articleId], 'Id, IdArticle, Item');

        return array_values(array_map(
            static fn(object $row): CarouselItemRow => CarouselItemRow::fromStdClass($row),
            $rows
        ));
    }

    /**
     * @param list<string> $paths
     * @return array<string, bool>
     */
    public function getPathsUsedInGalery(array $paths): array
    {
        if (empty($paths)) {
            return [];
        }

        $stmt = $this->pdo->query("SELECT Item FROM Carousel");
        if ($stmt === false) {
            throw new RuntimeException('Query failed: SELECT Item FROM Carousel');
        }
        $items = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $used = [];
        foreach ($paths as $path) {
            foreach ($items as $item) {
                if (is_string($item) && str_contains($item, $path)) {
                    $used[$path] = true;
                    break;
                }
            }
        }

        return $used;
    }
}
