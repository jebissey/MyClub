<?php

namespace app\models;

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
            $this->set('Carousel', ['Item' => $item], ['id' => $data['id'], 'IdArticle' => $data['idArticle']]);
            return 'Élément mis à jour avec succès';
        } else {
            $this->set('Carousel', ['Item' => $item, 'IdArticle' => $data['idArticle']]);
            return 'Élément ajouté avec succès';
        }
    }
}
