<?php
class WalletDB {
    private PDO $pdo;

    public function __construct(string $dbFile = 'blockchain.db') {
        $this->pdo = new PDO('sqlite:' . $dbFile);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createTable();
    }

    private function createTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS wallets (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    address TEXT UNIQUE,
                    public_key TEXT,
                    private_key TEXT
                )";
        $this->pdo->exec($sql);
    }

    public function saveWallet(Wallet $wallet): void {
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO wallets (address, public_key, private_key)
            VALUES (:address, :public_key, :private_key)");
        $stmt->execute([
            ':address'     => $wallet->address,
            ':public_key'  => $wallet->publicKey,
            ':private_key' => $wallet->privateKey
        ]);
    }

    public function getWallets(): array {
        $stmt = $this->pdo->query("SELECT * FROM wallets ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Neu: Diese Methode gibt das Wallet als assoziatives Array zurück oder null, wenn es nicht gefunden wurde.
    public function getWalletByAddress(string $address): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM wallets WHERE address = :address LIMIT 1");
        $stmt->execute([':address' => $address]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
        return $wallet !== false ? $wallet : null;
    }
}
?>
