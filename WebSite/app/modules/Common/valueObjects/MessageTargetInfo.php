<?php

// app/modules/Common/valueObjects/MessageTargetInfo.php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class MessageTargetInfo
{
    /** @param 'article'|'event'|'group' $kind */
    public function __construct(
        public readonly string $kind,
        public readonly int $id,
        public readonly string $label,
        public readonly ?int $articleAuthorId = null,
        public readonly ?int $eventCreatorId = null,
    ) {
    }
}
