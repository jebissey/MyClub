<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\helpers\GravatarHandler;
use app\helpers\To;
use app\helpers\WebApp;
use app\modules\Common\valueObjects\AvatarInfo;
use app\modules\Common\valueObjects\LocatedMemberRow;
use app\modules\Common\valueObjects\MemberMapMarker;

final readonly class MemberMapService
{
    private const LOCATION_PATTERN = '/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/';

    public function __construct(
        private GravatarHandler $gravatarHandler
    ) {
    }

    /**
     * Converts located members into map markers.
     * Members without a valid "lat, lng" location are skipped.
     *
     * @param list<LocatedMemberRow> $members
     * @return list<MemberMapMarker>
     */
    public function getLocationData(array $members): array
    {
        $markers = [];
        foreach ($members as $member) {
            if (empty($member->location) || preg_match(self::LOCATION_PATTERN, $member->location) !== 1) {
                continue;
            }
            [$lat, $lng] = explode(',', $member->location);
            $markers[] = new MemberMapMarker(
                id: $member->id,
                name: $member->firstName . ' ' . $member->lastName,
                nickname: $member->nickName,
                avatar: AvatarInfo::fromMember(
                    useGravatar: $member->useGravatar,
                    email: $member->email,
                    avatar: $member->avatar,
                    userImg: WebApp::getUserImg(
                        To::bool($member->useGravatar),
                        $member->email,
                        $member->avatar,
                        $this->gravatarHandler
                    ),
                ),
                lat: trim($lat),
                lng: trim($lng),
                myPublicData: $member->myPublicDataInPresentationDirectory,
            );
        }

        return $markers;
    }
}
