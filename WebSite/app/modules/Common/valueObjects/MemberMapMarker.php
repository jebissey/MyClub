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
        public int $id,
        public string $name,
        public ?string $nickname,
        public AvatarInfo $avatar,
        public string $lat,
        public string $lng,
        public ?string $myPublicData,
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
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'avatar' => $this->avatar,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'myPublicData' => $this->myPublicData,
        ];
    }
}
