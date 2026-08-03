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
        /** @var object{ThisIsTestSite: int}|false $meta */
        $meta = $this->get('Metadata', ['Id' => 1], 'ThisIsTestSite');

        return $meta !== false && $meta->ThisIsTestSite === 1;
    }

    public function getForcedLanguage(): string
    {
        /** @var object{ThisIsForcedLanguage: string|null}|false $meta */
        $meta = $this->get(
            'Metadata',
            ['Id' => 1],
            'ThisIsForcedLanguage'
        );

        return $meta !== false
            ? ($meta->ThisIsForcedLanguage ?? '')
            : '';
    }

    public function getProdSiteUrl(): string
    {
        /** @var object{ThisIsProdSiteUrl: string|null}|false $meta */
        $meta = $this->get(
            'Metadata',
            ['Id' => 1],
            'ThisIsProdSiteUrl'
        );

        return $meta !== false
            ? ($meta->ThisIsProdSiteUrl ?? '')
            : '';
    }

    public function setForcedLanguage(?string $language): void
    {
        $this->set(
            'Metadata',
            ['ThisIsForcedLanguage' => $language],
            ['Id' => 1]
        );
    }
}
