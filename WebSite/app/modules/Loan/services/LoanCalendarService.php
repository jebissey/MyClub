<?php

declare(strict_types=1);

namespace app\modules\Loan\services;

use app\models\LoanRecordDataHelper;
use app\models\LoanReservationDataHelper;
use app\modules\Loan\valueObjects\LoanCalendarEvent;
use RuntimeException;

final readonly class LoanCalendarService
{
    private const COLOR_LOAN_ACTIVE   = '#0d6efd';
    private const COLOR_LOAN_RETURNED = '#198754';
    private const COLOR_LOAN_OVERDUE  = '#dc3545';
    private const COLOR_RESERVATION   = '#fd7e14';

    public function __construct(
        private LoanRecordDataHelper $loanRecordDataHelper,
        private LoanReservationDataHelper $loanReservationDataHelper,
    ) {
    }

    /**
     * Returns the FullCalendar events (loans and reservations) for the period [start, end].
     * @return list<LoanCalendarEvent>
     */
    public function getEvents(string $start, string $end): array
    {
        return [
            ...$this->loanEvents($start, $end),
            ...$this->reservationEvents($start, $end),
        ];
    }

    /** @return list<LoanCalendarEvent> */
    private function loanEvents(string $start, string $end): array
    {
        $events = [];
        foreach ($this->loanRecordDataHelper->getLoansBetween($start, $end) as $loan) {
            $color = match ($loan->status) {
                'returned' => self::COLOR_LOAN_RETURNED,
                'overdue'  => self::COLOR_LOAN_OVERDUE,
                default    => self::COLOR_LOAN_ACTIVE,
            };

            $lastDay = ($loan->returnDate !== null && $loan->returnDate !== '')
                ? $loan->returnDate
                : $loan->dueDate;

            $events[] = new LoanCalendarEvent(
                id: 'loan-' . $loan->id,
                title: $loan->itemName . ' → ' . $loan->borrowerName,
                start: $loan->loanDate,
                end: $this->nextDay($lastDay, $loan->id),
                backgroundColor: $color,
                borderColor: $color,
                extendedProps: [
                    'type'   => 'loan',
                    'id'     => $loan->id,
                    'status' => $loan->status,
                ],
            );
        }
        return $events;
    }

    /** @return list<LoanCalendarEvent> */
    private function reservationEvents(string $start, string $end): array
    {
        $events = [];
        foreach ($this->loanReservationDataHelper->getActiveReservationsBetween($start, $end) as $reservation) {
            $events[] = new LoanCalendarEvent(
                id: 'res-' . $reservation->id,
                title: $reservation->itemName . ' – ' . $reservation->userName,
                start: $reservation->reservationDate . 'T' . $reservation->startTime,
                end: $reservation->reservationDate . 'T' . $reservation->endTime,
                backgroundColor: self::COLOR_RESERVATION,
                borderColor: self::COLOR_RESERVATION,
                extendedProps: [
                    'type' => 'reservation',
                    'id'   => $reservation->id,
                ],
            );
        }
        return $events;
    }

    /** FullCalendar treats the end of an all-day event as exclusive, so add one day. */
    private function nextDay(string $date, int $loanId): string
    {
        $timestamp = strtotime($date . ' +1 day');
        if ($timestamp === false) {
            throw new RuntimeException("Invalid date for loan #{$loanId}: {$date}");
        }
        return date('Y-m-d', $timestamp);
    }
}
