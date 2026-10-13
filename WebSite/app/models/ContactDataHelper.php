<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

/**
 * Data access for Contact (the Individual subtype for non-members).
 */
final class ContactDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * Creates the contact if needed, then issues a fresh token for it.
     *
     * @return string The new token
     */
    public function issueRegistrationToken(string $email, string $name): string
    {
        // Contact is a subtype of Individual
        /** @var object{Id: int}|false $individual */
        $individual = $this->get('Individual', ['Email' => $email], '*');
        $token = bin2hex(random_bytes(32));

        if ($individual === false) {
            // Create Individual + Contact
            $this->set('Individual', [
                'Type'      => 'Contact',
                'Email'     => $email,
                'FirstName' => $name,
                'LastName'  => '',
                'NickName'  => $name,
            ]);
            $individualId = (int) $this->pdo->lastInsertId();

            $this->set('Contact', [
                'Id'             => $individualId,
                'Token'          => $token,
                'TokenCreatedAt' => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Refresh the Contact token
            $this->set(
                'Contact',
                [
                    'Token'          => $token,
                    'TokenCreatedAt' => date('Y-m-d H:i:s'),
                ],
                ['Id' => $individual->Id]
            );
        }

        return $token;
    }
}
