<?php

declare(strict_types=1);

namespace app\helpers;

class GravatarHandler
{
    private string $defaultImage = 'mp';
    private int $size = 48;
    private string $rating = 'g';

    private array $existenceCache = [];

    public function getGravatar(string $email, bool $useGravatar): string
    {
        if (!$useGravatar) return '';
        if (!$this->hasRealGravatar($email)) return '';
        return $this->buildGravatarUrl($email);
    }

    #region Private functions
    private function hasRealGravatar(string $email): bool
    {
        $email = strtolower(trim($email));
        if (isset($this->existenceCache[$email])) return $this->existenceCache[$email];

        $hash = md5($email);
        $url  = "https://www.gravatar.com/avatar/{$hash}?d=404&s={$this->size}";
        $headers = @get_headers($url);
        $exists = $headers && strpos($headers[0], '200') !== false;
        $this->existenceCache[$email] = $exists;

        return $exists;
    }

    private function buildGravatarUrl(string $email): string
    {
        $hash = md5(strtolower(trim($email)));

        return sprintf(
            "https://www.gravatar.com/avatar/%s?s=%d&d=%s&r=%s",
            $hash,
            $this->size,
            $this->defaultImage,
            $this->rating
        );
    }
}
