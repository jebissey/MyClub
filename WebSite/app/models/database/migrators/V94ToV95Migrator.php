<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;
use app\modules\Common\interfaces\DatabaseMigratorInterface;

final class V94ToV95Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        // Nowdoc (quoted marker): no variable interpolation, so "%1$s" is kept as is.
        $pdo->exec(<<<'SQL'
-- Contact page: hard-coded texts moved to translations
INSERT OR REPLACE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES
('contact.page_title',
'Contact',
'Contact',
'Kontakt'),

('contact.heading.contact',
'Contact us',
'Nous contacter',
'Skontaktuj się z nami'),

('contact.heading.event',
'Register for the event on',
'Vous inscrire à l''événement du',
'Zapisz się na wydarzenie z dnia'),

('contact.honeypot.label',
'Leave this field empty',
'Laisser ce champ vide',
'Pozostaw to pole puste'),

('contact.field.name',
'Last name & First name *',
'Nom & Prénom *',
'Nazwisko i imię *'),

('contact.field.email',
'Email *',
'Email *',
'E-mail *'),

('contact.field.message',
'Your message *',
'Votre message *',
'Twoja wiadomość *'),

('contact.field.locked',
'These details come from your account and cannot be changed here.',
'Ces informations proviennent de votre compte et ne peuvent pas être modifiées ici.',
'Te dane pochodzą z Twojego konta i nie można ich tutaj zmienić.'),

('contact.submit',
'Send',
'Envoyer',
'Wyślij'),

('contact.error.name_required',
'Last name and first name are required.',
'Nom et prénom sont requis.',
'Nazwisko i imię są wymagane.'),

('contact.error.email_required',
'A valid email is required.',
'Un email valide est requis.',
'Wymagany jest poprawny adres e-mail.'),

('contact.error.message_required',
'The message is required.',
'Le message est requis.',
'Wiadomość jest wymagana.'),

('contact.error.send_failed',
'An error occurred while sending the message. Please try again.',
'Une erreur est survenue lors de l''envoi du message. Veuillez réessayer.',
'Podczas wysyłania wiadomości wystąpił błąd. Spróbuj ponownie.'),

('contact.success.sent',
'Message sent successfully.',
'Message envoyé avec succès.',
'Wiadomość została wysłana.'),

('contact.email.subject',
'Contact message from %1$s (%2$s)',
'Message de contact de %1$s (%2$s)',
'Wiadomość kontaktowa od %1$s (%2$s)');
SQL);

        return 95;
    }
}
