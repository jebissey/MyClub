<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\enums\AvatarKind;
use JsonSerializable;

/**
 * What to display for a person's avatar: a picture (e.g. Gravatar), an emoji, or a placeholder.
 * Single source of truth shared by the directory, the members map and the carpooling map.
 */
final readonly class AvatarInfo implements JsonSerializable
{
    public function __construct(
        public AvatarKind $Kind,
        public string $Value,
    ) {
    }

    /**
     * Mirrors the rules of the directory:
     * - Gravatar selected, with an email and an image URL  → picture
     * - an avatar is defined and the server returned an emoji → emoji
     * - anything else (including a Gravatar selected that cannot be used) → placeholder
     *
     * @param string|null $userImg Value returned by WebApp::getUserImg(): an image URL or an emoji
     */
    public static function fromMember(?string $useGravatar, ?string $email, ?string $avatar, ?string $userImg): self
    {
        $image = trim((string) $userImg);
        if ($image === '') {
            return new self(AvatarKind::Placeholder, '');
        }
        $isUrl = preg_match('#^(https?://|/|data:image/)#i', $image) === 1;

        if ($useGravatar === 'yes' && trim((string) $email) !== '' && $isUrl) {
            return new self(AvatarKind::Image, $image);
        }
        if (trim((string) $avatar) !== '' && !$isUrl) {
            return new self(AvatarKind::Emoji, $image);
        }

        return new self(AvatarKind::Placeholder, '');
    }

    /**
     * @return array{kind: string, value: string}
     */
    public function jsonSerialize(): array
    {
        return ['kind' => $this->Kind->value, 'value' => $this->Value];
    }
}
