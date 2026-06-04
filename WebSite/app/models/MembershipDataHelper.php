<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class MembershipDataHelper extends Data
{
	public function __construct(Application $application)
	{
		parent::__construct($application);
	}

	// ─── Season helpers ───────────────────────────────────────────────────────

	/**
	 * Returns the current season string, e.g. '2024-2025'.
	 * Season starts on the month defined by the Membership_SeasonStart setting (default: 09).
	 */
	public function currentSeason(): string
	{
		$startMonth = (int)$this->getSetting('Membership_SeasonStart', '09');
		$now        = new \DateTimeImmutable();
		$month      = (int)$now->format('n');
		$year       = (int)$now->format('Y');

		if ($month >= $startMonth) {
			return $year . '-' . ($year + 1);
		}
		return ($year - 1) . '-' . $year;
	}

	// ─── Read ─────────────────────────────────────────────────────────────────

	/** Returns the membership row for a given person and season, or false. */
	public function getForPersonAndSeason(int $personId, string $season): object|false
	{
		return $this->get('Membership', [
			'PersonId' => $personId,
			'Season'   => $season,
		]);
	}

	/** Returns all memberships for a person, most recent first. */
	public function getAllForPerson(int $personId): array
	{
		return $this->query(
			"SELECT * FROM Membership WHERE PersonId = ? ORDER BY Season DESC",
			[$personId]
		) ?: [];
	}

	// ─── Write ────────────────────────────────────────────────────────────────

	/**
	 * Creates a new pending membership row and returns its Id.
	 */
	public function createPending(int $personId, string $season, int $amountCents): int
	{
		return (int)$this->set('Membership', [
			'PersonId' => $personId,
			'Season'   => $season,
			'Amount'   => $amountCents,
			'Status'   => 'pending',
		]);
	}

	/**
	 * Stores the HelloAsso checkout intent id on a pending row.
	 */
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

	/**
	 * Marks a membership as paid via the HelloAsso webhook.
	 * Matches by HelloAsso checkout intent id.
	 */
	public function markPaidByIntentId(string $intentId, string $orderId): bool
	{
		$row = $this->query(
			"SELECT Id FROM Membership WHERE HelloAssoCheckoutIntentId = ? AND Status = 'pending' LIMIT 1",
			[$intentId]
		);

		if (empty($row)) {
			return false;
		}

		$this->set(
			'Membership',
			[
				'Status'              => 'paid',
				'HelloAssoOrderId'    => $orderId,
				'PaidAt'              => date('Y-m-d H:i:s'),
				'UpdatedAt'           => date('Y-m-d H:i:s'),
			],
			['Id' => (int)$row[0]->Id]
		);

		return true;
	}

	/**
	 * Cancels a membership row.
	 */
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

	// ─── Settings shortcuts ───────────────────────────────────────────────────

	public function getAmountCents(): int
	{
		return (int)$this->getSetting('Membership_Amount', '1234567');
	}

	public function getHelloAssoConfig(): array
	{
		return [
			'orgSlug'  => $this->getSetting('HelloAsso_OrgSlug',  ''),
			'formSlug' => $this->getSetting('HelloAsso_FormSlug', ''),
		];
	}
}
