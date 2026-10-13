<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use JsonSerializable;

/**
 * A located member as displayed on a map.
 * Serialized to JSON with the keys expected by the map JavaScript (camelCase).
 */
final readonly class MemberMapMarker implements JsonSerializable
{
    public function __construct(
        public int $Id,
        public string $Name,
        public ?string $Nickname,
        public AvatarInfo $Avatar,
        public string $Lat,
        public string $Lng,
        public ?string $MyPublicData,
    ) {
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     nickname: ?string,
     *     avatar: AvatarInfo,
     *     lat: string,
     *     lng: string,
     *     myPublicData: ?string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->Id,
            'name' => $this->Name,
            'nickname' => $this->Nickname,
            'avatar' => $this->Avatar,
            'lat' => $this->Lat,
            'lng' => $this->Lng,
            'myPublicData' => $this->MyPublicData,
        ];
    }
}
