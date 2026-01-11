<?php

declare(strict_types=1);

final class Ledger
{
    private PDO $pdo;
    private string $logFile;

    public function __construct(string $databasePath, string $logFile)
    {
        $directory = dirname($databasePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $this->pdo = new PDO('sqlite:' . $databasePath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->logFile = $logFile;
        $this->ensureSchema();
    }

    public function addTransaction(int $userId, string $type, int $amount, array $meta = []): array
    {
        if ($amount === 0) {
            throw new InvalidArgumentException('Le montant ne peut pas être nul.');
        }

        if (!in_array($type, ['bonus_daily', 'game_win', 'game_bet', 'adjustment'], true)) {
            throw new InvalidArgumentException('Type de transaction invalide.');
        }

        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);
        $metaJson = json_encode($meta, JSON_THROW_ON_ERROR);

        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions (user_id, type, amount, meta, created_at) VALUES (:user_id, :type, :amount, :meta, :created_at)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':amount' => $amount,
            ':meta' => $metaJson,
            ':created_at' => $now,
        ]);

        $transactionId = (int) $this->pdo->lastInsertId();
        $this->logEvent('transaction', [
            'id' => $transactionId,
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
        ]);

        return [
            'id' => $transactionId,
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'meta' => $meta,
            'created_at' => $now,
        ];
    }

    public function getBalance(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(amount), 0) AS balance FROM transactions WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function getHistory(int $userId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, type, amount, meta, created_at FROM transactions WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getLastTransactions(int $userId, int $limit = 5): array
    {
        return $this->getHistory($userId, $limit);
    }

    public function hasDailyBonus(int $userId, DateTimeImmutable $today): bool
    {
        $startOfDay = $today->setTime(0, 0);
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM transactions WHERE user_id = :user_id AND type = :type AND created_at >= :start'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':type' => 'bonus_daily',
            ':start' => $startOfDay->format(DateTimeInterface::ATOM),
        ]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    private function ensureSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                type TEXT NOT NULL,
                amount INTEGER NOT NULL,
                meta TEXT NOT NULL,
                created_at TEXT NOT NULL
            )'
        );
    }

    private function normalizeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['amount'] = (int) $row['amount'];
            $row['meta'] = json_decode($row['meta'], true, 512, JSON_THROW_ON_ERROR);
        }
        unset($row);
        return $rows;
    }

    private function logEvent(string $event, array $context): void
    {
        $line = json_encode([
            'time' => date(DATE_ATOM),
            'event' => $event,
            'context' => $context,
        ], JSON_UNESCAPED_SLASHES);
        $directory = dirname($this->logFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        file_put_contents($this->logFile, $line . PHP_EOL, FILE_APPEND);
    }
}
