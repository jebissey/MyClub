<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class OrderReplyDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function insertOrUpdate(int $personId, int $orderId, string $answers): void
    {
        $existingReply = $this->get('OrderReply', ['IdPerson' => $personId, 'IdOrder' => $orderId], 'Id');
        if ($existingReply) {
            $this->set('OrderReply', [
                'Answers' => $answers,
                'LastUpdate' => date('Y-m-d H:i:s')
            ], ['Id' => $existingReply->Id]);
        } else {
            $this->set('OrderReply', [
                'IdPerson'   => $personId,
                'IdOrder'   => $orderId,
                'Answers' => $answers,
                'LastUpdate' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
