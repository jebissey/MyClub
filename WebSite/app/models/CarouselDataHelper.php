<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use RuntimeException;
use stdClass;
use app\helpers\Application;

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


    /**
     * @return list<stdClass>
     */
    public function getCarouselItems(int $articleId): array
    {
        return array_values(
            $this->gets('Carousel', ['IdArticle' => $articleId], 'Id, IdArticle, Item')
        );
    }

    /**
     * @param array<int, string> $paths
     * @return array<string, bool>
     */
    public function getPathsUsedInGalery(array $paths): array
    {
        if (empty($paths)) {
            return [];
        }

        $stmt = $this->pdo->query("SELECT Item FROM Carousel");
        if ($stmt === false) {
            throw new RuntimeException('Échec de la requête SELECT Item FROM Carousel');
        }
        $items = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $used = [];
        foreach ($paths as $path) {
            foreach ($items as $item) {
                if (str_contains($item, $path)) {
                    $used[$path] = true;
                    break;
                }
            }
        }
        return $used;
    }
}
