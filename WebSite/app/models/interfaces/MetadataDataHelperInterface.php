<?php

// app/models/MetadataDataHelperInterface.php

declare(strict_types=1);

namespace app\models\interfaces;

interface MetadataDataHelperInterface
{
    public function isTestSite(): bool;

    public function getForcedLanguage(): string;

    public function getProdSiteUrl(): string;

    public function setForcedLanguage(?string $language): void;
}
