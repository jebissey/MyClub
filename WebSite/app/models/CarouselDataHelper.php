<?php

declare(strict_types=1);

namespace app\models;

use PDO;

use app\helpers\Application;

class CarouselDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function set_(array $data, string $item): string
    {
        if (!empty($data['id'])) {
            $this->set('Carousel', ['Item' => $item], [
                'id' => $data['id'],
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


    public function getPathsUsedInGalery(array $paths): array
    {
        if (empty($paths)) return [];

        $stmt = $this->pdo->query("SELECT Item FROM Carousel");
        $items = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $used = [];
        foreach ($paths as $path) {
            foreach ($items as $item) {
                if ($item !== null && str_contains($item, $path)) {
                    $used[$path] = true;
                    break;
                }
            }
        }
        return $used;
    }
}
