<?php
declare(strict_types=1);

namespace app\helpers;

class GravatarHandler
{
    private string $defaultImage = 'mp';    // 'mp' pour un placeholder générique
    private int $size = 48;                 // Taille par défaut en pixels
    private string $rating = 'g';           // Note 'g' pour tout public

    public function hasGravatar(string $email): bool
    {
        $hash = md5(strtolower(trim($email)));
        $uri = "https://www.gravatar.com/avatar/{$hash}?d=404";

        $headers = @get_headers($uri);
        return $headers && strpos($headers[0], '200') !== false;
    }

    public function getGravatar(string $email): string
    {
        return $this->hasGravatar($email) ?  $this->getGravatarUrl($email) : '';
    }

    private function getGravatarUrl(string $email): string
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
