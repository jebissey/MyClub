<?php
declare(strict_types=1);
namespace app\models\database\migrators;
use PDO;
use app\interfaces\DatabaseMigratorInterface;

class V35ToV36Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $sql = <<<SQL
INSERT INTO Languages (Name, en_US, fr_FR, pl_PL)
VALUES
('navbar.webmaster.turnstile',
'Anti-spam protection (Turnstile)',
'Protection anti-spam (Turnstile)',
'Ochrona antyspamowa (Turnstile)'),

('turnstile.title',
'Anti-spam protection (Turnstile)',
'Protection anti-spam (Turnstile)',
'Ochrona antyspamowa (Turnstile)'),

('turnstile.alert.not_configured',
'No key configured — Turnstile verification is disabled. The contact form remains protected by the honeypot, timing check and rate limiting.',
'Aucune clé configurée — la vérification Turnstile est désactivée. Le formulaire de contact reste protégé par le honeypot, le timing et le rate limiting.',
'Brak skonfigurowanego klucza — weryfikacja Turnstile jest wyłączona. Formularz kontaktowy jest nadal chroniony przez honeypot, kontrolę czasu i rate limiting.'),

('turnstile.info.get_keys',
'Get your free keys at Cloudflare Turnstile (up to 1 million verifications/month).',
'Obtenez vos clés gratuitement sur Cloudflare Turnstile (jusqu''à 1 million de vérifications/mois).',
'Uzyskaj bezpłatne klucze na Cloudflare Turnstile (do 1 miliona weryfikacji/miesiąc).'),

('turnstile.info.localhost',
'For local testing, use the universal Cloudflare keys.',
'Pour les tests en local, utilisez les clés universelles Cloudflare.',
'Do testów lokalnych użyj uniwersalnych kluczy Cloudflare.'),

('turnstile.field.site_key',
'Site Key',
'Site Key',
'Klucz witryny'),

('turnstile.field.site_key.hint',
'Integrated into the contact form HTML.',
'Intégrée dans le HTML du formulaire de contact.',
'Zintegrowany z kodem HTML formularza kontaktowego.'),

('turnstile.field.site_key.public',
'public key',
'clé publique',
'klucz publiczny'),

('turnstile.field.secret_key',
'Secret Key',
'Secret Key',
'Klucz tajny'),

('turnstile.field.secret_key.private',
'private key',
'clé privée',
'klucz prywatny'),

('turnstile.field.secret_key.not_configured',
'Not configured',
'Non configurée',
'Nieskonfigurowany'),

('turnstile.field.secret_key.hint',
'Leave empty to keep the current key.',
'Laisser vide pour conserver la clé actuelle.',
'Pozostaw puste, aby zachować bieżący klucz.');
SQL;
        $pdo->exec($sql);

        return 36;
    }
}