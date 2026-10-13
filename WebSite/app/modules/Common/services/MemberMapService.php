<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\helpers\GravatarHandler;
use app\helpers\WebApp;
use app\modules\Common\valueObjects\AvatarInfo;
use app\modules\Common\valueObjects\LocatedMemberRow;
use app\modules\Common\valueObjects\MemberMapMarker;
use app\modules\Common\valueObjects\Person;

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
            if (empty($member->Location) || preg_match(self::LOCATION_PATTERN, $member->Location) !== 1) {
                continue;
            }
            [$lat, $lng] = explode(',', $member->Location);
            $markers[] = new MemberMapMarker(
                Id: $member->Id,
                Name: $member->FirstName . ' ' . $member->LastName,
                Nickname: $member->NickName,
                Avatar: AvatarInfo::fromMember(
                    useGravatar: $member->UseGravatar,
                    email: $member->Email,
                    avatar: $member->Avatar,
                    userImg: WebApp::getUserImg(Person::fromRow($member), $this->gravatarHandler),
                ),
                Lat: trim($lat),
                Lng: trim($lng),
                MyPublicData: $member->MyPublicDataInPresentationDirectory,
            );
        }

        return $markers;
    }
}
