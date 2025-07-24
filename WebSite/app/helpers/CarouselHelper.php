<?php

namespace app\helpers;


class CarouselHelper extends Data
{
    public function delete_($id)
    {
        $this->fluent->deleteFrom('Carousel')
            ->where('Id', $id)
            ->execute();
    }

    public function get_($id)
    {
        return $this->fluent->from('Carousel')
            ->where('Id', $id)
            ->fetch();
    }

    public function getsForArticle($id)
    {
        $this->fluent->from('Carousel')->where('IdArticle', $id)->fetchAll();
    }

    public function getByArticle($idArticle)
    {
        return $this->fluent->from('Carousel')->where('IdArticle', $idArticle)->fetchAll();
    }

    public function set_($data, $item): string
    {
        if (!empty($data['id'])) {
            $this->fluent->update('Carousel')
                ->set([
                    'Item' => $item
                ])
                ->where('Id', $data['id'])
                ->where('IdArticle', $data['idArticle'])
                ->execute();
            $message = 'Élément mis à jour avec succès';
        } else {
            $this->fluent->insertInto('Carousel')
                ->values([
                    'IdArticle' => $data['idArticle'],
                    'Item' => $item
                ])
                ->execute();
            $message = 'Élément ajouté avec succès';
        }
        return $message;
    }
}
