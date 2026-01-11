<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/ledger.php';

$databasePath = __DIR__ . '/../data/test_credits.sqlite';
$logPath = __DIR__ . '/../data/test_app.log';

if (file_exists($databasePath)) {
    unlink($databasePath);
}

$ledger = new Ledger($databasePath, $logPath);

$userId = 1;
$ledger->addTransaction($userId, 'bonus_daily', 100, ['source' => 'test']);
$ledger->addTransaction($userId, 'game_bet', -25, ['game' => 'slots']);
$ledger->addTransaction($userId, 'game_win', 40, ['game' => 'slots']);

$balance = $ledger->getBalance($userId);
if ($balance !== 115) {
    fwrite(STDERR, "Balance incorrecte: {$balance}\n");
    exit(1);
}

$history = $ledger->getHistory($userId, 10);
if (count($history) !== 3) {
    fwrite(STDERR, "Historique incorrect: " . count($history) . "\n");
    exit(1);
}

$today = new DateTimeImmutable('now', new DateTimeZone('UTC'));
if (!$ledger->hasDailyBonus($userId, $today)) {
    fwrite(STDERR, "Bonus quotidien non détecté.\n");
    exit(1);
}

echo "Ledger assertions passed.\n";
