<?php

declare(strict_types=1);

namespace app\helpers;

use stdClass;
use app\modules\Common\valueObjects\ChatMessage;

/**
 * Maps raw Message rows (joined with Member and Individual) to ChatMessage value objects.
 */
final class ChatMessageMapper
{
    public function __construct(private readonly GravatarHandler $gravatarHandler = new GravatarHandler())
    {
    }

    /**
     * @param list<stdClass> $rows
     * @return list<ChatMessage>
     */
    public function mapAll(array $rows): array
    {
        return array_map(fn(stdClass $row): ChatMessage => $this->map($row), $rows);
    }

    public function map(stdClass $row): ChatMessage
    {
        $id = To::requireInt($row->Id ?? null);
        $personId = To::requireInt($row->PersonId ?? null);
        $text = To::requireStr($row->Text ?? null);
        $lastUpdate = To::requireStr($row->LastUpdate ?? null);

        $email = To::nullableStr($row->Email ?? null);
        $avatar = To::nullableStr($row->Avatar ?? null);

        // The UseGravatar column uses the values 'yes' and 'no'.
        $useGravatar = To::bool($row->UseGravatar ?? null);

        return new ChatMessage(
            id: $id,
            personId: $personId,
            text: $text,
            lastUpdate: $lastUpdate,
            imagePath: To::nullableStr($row->ImagePath ?? null),
            email: $email,
            avatar: $avatar,
            useGravatar: $useGravatar,
            nickName: To::nullableStr($row->NickName ?? null),
            firstName: To::nullableStr($row->FirstName ?? null),
            lastName: To::nullableStr($row->LastName ?? null),
            userImg: WebApp::computeUserImg(
                $useGravatar,
                $email,
                $avatar,
                $this->gravatarHandler
            ),
            timeAgo: MyClubDateTime::calculateTimeAgo($lastUpdate),
        );
    }
}
