<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class MetadataDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function isTestSite(): bool
    {
        return $this->get('Metadata', ['Id' => 1], 'ThisIsTestSite')->ThisIsTestSite == 1;
    }

    public function getProdSiteUrl(): string
    {
        return $this->get('Metadata', ['Id' => 1], 'ThisIsProdSiteUrl')->ThisIsProdSiteUrl;
    }
}
