<?php

class GravatarHandler {
    private string $defaultImage = 'mp'; // 'mp' pour un placeholder générique
    private int $size = 64; // Taille par défaut en pixels
    private string $rating = 'g'; // Note 'g' pour tout public

    public function hasGravatar(string $email): bool {
        $hash = md5(strtolower(trim($email)));
        $uri = "https://www.gravatar.com/avatar/{$hash}?d=404";
        
        $headers = @get_headers($uri);
        return $headers && strpos($headers[0], '200') !== false;
    }

    public function displayGravatar(string $email, ?string $altText = null): string {
        if (!$this->hasGravatar($email)) {
            return ''; 
        }

        $url = $this->getGravatarUrl($email);
        $alt = htmlspecialchars($altText ?? "Gravatar de {$email}");
        
        return sprintf(
            '<img id="userAvatar" src="%s" alt="%s" width="%d" height="%d" class="gravatar">',
            $url,
            $alt,
            $this->size,
            $this->size
        );
    }

    private function getGravatarUrl(string $email): string {
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