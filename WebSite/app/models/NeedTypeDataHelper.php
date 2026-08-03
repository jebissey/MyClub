<?php

declare(strict_types=1);

namespace app\models;

use RuntimeException;
use app\helpers\Application;

class NeedTypeDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function insertOrUpdate(?int $id, string $name): int
    {
        if ($id === null) {
            $result = $this->set(
                'NeedType',
                ['Name' => $name]
            );

            if (!is_int($result)) {
                throw new RuntimeException('Impossible de récupérer l\'ID du NeedType créé.');
            }

            return $result;
        }

        $result = $this->set(
            'NeedType',
            ['Name' => $name],
            ['Id' => $id]
        );

        if (!is_int($result)) {
            throw new RuntimeException('Impossible de mettre à jour le NeedType.');
        }

        return $result;
    }
}
