<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class KaraokeDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function cleanupOldClients(): void
    {
        $timeout = date('Y-m-d H:i:s', strtotime('-10 seconds'));
        $stmt = $this->pdo->prepare('DELETE FROM "KaraokeClient" WHERE "LastHeartbeat" < ?');
        $stmt->execute([$timeout]);
    }

    public function getOrCreateSession(string $sessionId, string $songName): int
    {
        $stmt = $this->pdo->prepare('SELECT "Id" FROM "KaraokeSession" WHERE "SessionId" = ?');
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if ($session) {
            return (int)$session->Id;
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO "KaraokeSession" ("SessionId", "SongId", "Status", "CreatedAt", "UpdatedAt")
            VALUES (?, ?, "waiting", datetime("now"), datetime("now"))
        ');
        $stmt->execute([$sessionId, $songName]);
        
        return (int)$this->pdo->lastInsertId();
    }

    public function countActiveClients(int $idSession): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM "KaraokeClient" WHERE "IdKaraokeSession" = ?');
        $stmt->execute([$idSession]);
        return (int)$stmt->fetchColumn();
    }

    public function registerClient(string $clientId, int $idSession, bool $isHost): bool
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO "KaraokeClient" ("ClientId", "IdKaraokeSession", "IsHost", "LastHeartbeat", "CreatedAt")
            VALUES (?, ?, ?, datetime("now"), datetime("now"))
            ON CONFLICT("ClientId") DO UPDATE SET
                "LastHeartbeat" = datetime("now"),
                "IsHost" = excluded."IsHost",
                "IdKaraokeSession" = excluded."IdKaraokeSession"
        ');
        
        return $stmt->execute([$clientId, $idSession, $isHost ? 1 : 0]);
    }

    public function updateHeartbeat(string $clientId): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE "KaraokeClient" 
            SET "LastHeartbeat" = datetime("now")
            WHERE "ClientId" = ?
        ');
        
        return $stmt->execute([$clientId]);
    }

    public function isClientHost(string $clientId, int $idSession): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT "IsHost" FROM "KaraokeClient" 
            WHERE "ClientId" = ? AND "IdKaraokeSession" = ?
        ');
        $stmt->execute([$clientId, $idSession]);
        $client = $stmt->fetch();
        
        return $client && $client->IsHost == 1;
    }

    public function getSessionBySessionId(string $sessionId): ?object
    {
        $stmt = $this->pdo->prepare('SELECT * FROM "KaraokeSession" WHERE "SessionId" = ?');
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function getSessionById(int $id): ?object
    {
        $stmt = $this->pdo->prepare('SELECT * FROM "KaraokeSession" WHERE "Id" = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function startCountdown(int $idSession): bool
    {
        $now = time();
        $delay = 5; // secondes avant démarrage global
        $countdownStart = $now;
        $playStartTime = $now + $delay;

        $stmt = $this->pdo->prepare('
            UPDATE "KaraokeSession"
            SET "Status" = "countdown",
                "CountdownStart" = ?,
                "PlayStartTime" = ?,
                "UpdatedAt" = datetime("now")
            WHERE "Id" = ?
        ');
        
        return $stmt->execute([$countdownStart, $playStartTime, $idSession]);
    }

    public function disconnectClient(string $clientId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM "KaraokeClient" WHERE "ClientId" = ?');
        return $stmt->execute([$clientId]);
    }

    public function deleteSessionIfEmpty(int $idSession): void
    {
        $count = $this->countActiveClients($idSession);
        
        if ($count === 0) {
            $stmt = $this->pdo->prepare('DELETE FROM "KaraokeSession" WHERE "Id" = ?');
            $stmt->execute([$idSession]);
        }
    }
}
