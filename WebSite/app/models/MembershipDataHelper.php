<?php

declare(strict_types=1);

namespace app\models;

use DateTimeImmutable;
use stdClass;
use app\helpers\Application;
use app\helpers\To;
use app\modules\Membership\valueObjects\MembershipRow;

final class MembershipDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    public function attachCheckoutIntent(int $membershipId, string $intentId): void
    {
        $this->set(
            'Membership',
            [
                'HelloAssoCheckoutIntentId' => $intentId,
                'UpdatedAt'                 => date('Y-m-d H:i:s'),
            ],
            ['Id' => $membershipId]
        );
    }

    public function cancel(int $membershipId): void
    {
        $this->set(
            'Membership',
            [
                'Status'    => 'cancelled',
                'UpdatedAt' => date('Y-m-d H:i:s'),
            ],
            ['Id' => $membershipId]
        );
    }

    public function createPending(
        int $personId,
        string $season,
        int $amountCents
    ): int {
        return (int)$this->set('Membership', [
            'PersonId' => $personId,
            'Season'   => $season,
            'Amount'   => $amountCents,
            'Status'   => 'pending',
        ]);
    }

    public function currentSeason(): string
    {
        $startMonth = (int)$this->getSetting('Membership_SeasonStart', '09');
        $now        = new DateTimeImmutable();
        $month      = (int)$now->format('n');
        $year       = (int)$now->format('Y');

        if ($month >= $startMonth) {
            return $year . '-' . ($year + 1);
        }

        return ($year - 1) . '-' . $year;
    }

    /**
     * Returns all memberships for a person, most recent first.
     *
     * @return list<MembershipRow>
     */
    public function getAllForPerson(int $personId): array
    {
        $rows = $this->query(
            'SELECT * FROM Membership WHERE PersonId = ? ORDER BY Season DESC',
            [$personId]
        );

        if (!is_array($rows)) {
            return [];
        }

        $result = [];

        foreach ($rows as $row) {
            if ($row instanceof stdClass) {
                $result[] = $this->toMembershipRow($row);
            }
        }

        return $result;
    }

    public function getAmountCents(): int
    {
        return To::int($this->getSetting('Membership_Amount', '1234567'));
    }

    public function getForPersonAndSeason(
        int $personId,
        string $season
    ): MembershipRow|false {
        $membership = $this->get('Membership', [
            'PersonId' => $personId,
            'Season'   => $season,
        ], '*');

        if (!$membership instanceof stdClass) {
            return false;
        }

        return $this->toMembershipRow($membership);
    }

    /**
     * @return array{orgSlug:string, formSlug:string}
     */
    public function getHelloAssoConfig(): array
    {
        return [
            'orgSlug'  => $this->getSetting('HelloAsso_OrgSlug', ''),
            'formSlug' => $this->getSetting('HelloAsso_FormSlug', ''),
        ];
    }

    public function markPaidByIntentId(string $intentId, string $orderId): bool
    {
        $rows = $this->query(
            "SELECT Id FROM Membership WHERE HelloAssoCheckoutIntentId = ? AND Status = 'pending' LIMIT 1",
            [$intentId]
        );

        if (
            !is_array($rows)
            || !isset($rows[0])
            || !($rows[0] instanceof stdClass)
            || !isset($rows[0]->Id)
        ) {
            return false;
        }

        $membershipId = To::int($rows[0]->Id);

        $this->set(
            'Membership',
            [
                'Status'           => 'paid',
                'HelloAssoOrderId' => $orderId,
                'PaidAt'           => date('Y-m-d H:i:s'),
                'UpdatedAt'        => date('Y-m-d H:i:s'),
            ],
            ['Id' => $membershipId]
        );

        return true;
    }


    private function toMembershipRow(stdClass $row): MembershipRow
    {
        $paidAt = $row->PaidAt ?? null;

        return new MembershipRow(
            id: To::int($row->Id),
            personId: To::int($row->PersonId),
            season: To::str($row->Season ?? ''),
            status: To::str($row->Status ?? ''),
            amountCents: To::int($row->Amount),
            paidAt: is_string($paidAt) ? $paidAt : null,
        );
    }
}
