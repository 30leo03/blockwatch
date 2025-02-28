<?php
class PendingTransactionDB {
    private PDO $pdo;

    public function __construct(string $dbFile = 'blockchain.db') {
        $this->pdo = new PDO('sqlite:' . $dbFile);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createTable();
    }

    private function createTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS pending_transactions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    data TEXT
                )";
        $this->pdo->exec($sql);
    }

    /**
     * Speichert eine Transaktion (als JSON) in der Tabelle.
     */
    public function addTransaction($transaction): void {
        $stmt = $this->pdo->prepare("INSERT INTO pending_transactions (data) VALUES (:data)");
        // Nutze json_encode() auf dem von Transaction::jsonSerialize() zurückgegebenen Array
        $data = json_encode($transaction->jsonSerialize());
        $stmt->execute([':data' => $data]);
    }

    /**
     * Lädt alle gespeicherten Transaktionen und rekonstruiert Transaction-Objekte.
     */
    public function getAllTransactions(): array {
        $stmt = $this->pdo->query("SELECT data FROM pending_transactions ORDER BY id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $transactions = [];
        foreach ($rows as $row) {
            $data = json_decode($row['data'], true);
            if (isset($data['fromAddress'], $data['toAddress'], $data['amount'])) {
                $transactions[] = new Transaction($data['fromAddress'], $data['toAddress'], (float)$data['amount']);
            }
        }
        return $transactions;
    }

    /**
     * Löscht alle gespeicherten Pending Transactions.
     */
    public function clearTransactions(): void {
        $this->pdo->exec("DELETE FROM pending_transactions");
    }
}
?>
