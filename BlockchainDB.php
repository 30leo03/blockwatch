<?php
class BlockchainDB
{
  private PDO $pdo;

  public function __construct(string $dbFile = 'blockchain.db')
  {
    // Öffne (oder erstelle) die SQLite-Datenbank
    $this->pdo = new PDO('sqlite:' . $dbFile);
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->createTable();
  }

  private function createTable(): void
  {
    $sql = "CREATE TABLE IF NOT EXISTS blocks (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              idx INTEGER,
              timestamp INTEGER,
              data TEXT,
              previous_hash TEXT,
              hash TEXT,
              nonce INTEGER
            )";
    $this->pdo->exec($sql);
  }

  public function saveBlock(Block $block): void
  {
    $stmt = $this->pdo->prepare("INSERT INTO blocks (idx, timestamp, data, previous_hash, hash, nonce)
      VALUES (:idx, :timestamp, :data, :previous_hash, :hash, :nonce)");
    $stmt->execute([
      ':idx'           => $block->index,
      ':timestamp'     => $block->timestamp,
      ':data'          => json_encode($block->data),
      ':previous_hash' => $block->previousHash,
      ':hash'          => $block->hash,
      ':nonce'         => $block->nonce,
    ]);
  }

  public function getAllBlocks(): array
  {
    $stmt = $this->pdo->query("SELECT * FROM blocks ORDER BY idx ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
?>
