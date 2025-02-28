<?php
require_once 'Block.php';
require_once 'BlockchainDB.php';
require_once __DIR__ . '/../shared/Transaction.php';
require_once __DIR__ . '/../shared/PendingTransactionDB.php';

class Blockchain {
  /** @var Block[] */
  public array $chain;
  public int $difficulty;
  private BlockchainDB $db;
  private PendingTransactionDB $ptdb;
  private float $miningReward = 50.0; // fester Reward pro Block

  public function __construct() {
    $this->db = new BlockchainDB();
    $this->ptdb = new PendingTransactionDB();
    $storedBlocks = $this->db->getAllBlocks();

    if (empty($storedBlocks)) {
      // Erstelle Genesis-Block, wenn noch keine vorhanden sind
      $genesis = $this->createGenesisBlock();
      $this->chain = [$genesis];
      $this->db->saveBlock($genesis);
    } else {
      // Rekonstruiere die Blockchain aus der DB
      $this->chain = [];
      foreach ($storedBlocks as $row) {
        $data = json_decode($row['data'], true);
        if (is_array($data)) {
          $transactions = [];
          foreach ($data as $txData) {
            if (isset($txData['fromAddress'], $txData['toAddress'], $txData['amount'])) {
              $transactions[] = new Transaction($txData['fromAddress'], $txData['toAddress'], (float)$txData['amount']);
            }
          }
          $blockData = $transactions;
        } else {
          $blockData = $row['data'];
        }
        $block = new Block($row['idx'], $blockData, $row['previous_hash']);
        $block->timestamp = (int)$row['timestamp'];
        $block->nonce = (int)$row['nonce'];
        $block->hash = $row['hash'];
        $this->chain[] = $block;
      }
    }
    $this->difficulty = 4;
  }

  private function createGenesisBlock(): Block {
    return new Block(0, "Genesis Block", "0");
  }

  public function getLatestBlock(): Block {
    return $this->chain[count($this->chain) - 1];
  }

  /**
   * Fügt eine neue Transaktion in den persistente Transaktions-Pool ein.
   * Prüft, ob der Absender (außer coinbase) genügend Guthaben hat.
   */
  public function createTransaction(Transaction $transaction): void {
    // Bei coinbase-Transaktionen (fromAddress = "0") entfällt die Signaturprüfung.
    if ($transaction->fromAddress !== "0") {
      if (!$transaction->isValid()) {
        throw new Exception("Transaction is not valid");
      }
      if ($this->getBalanceOfAddress($transaction->fromAddress) < $transaction->amount) {
        throw new Exception("Insufficient funds for address " . $transaction->fromAddress);
      }
    }
    // Speichere die Transaktion im pending-Pool (persistiert oder im Speicher, je nach Implementierung)
    $this->ptdb->addTransaction($transaction);
  }


  /**
   * Verarbeitet alle persistierten ausstehenden Transaktionen:
   * - Lädt alle Pending Transaktionen aus der DB.
   * - Fügt eine coinbase-Transaktion hinzu, die den Miner belohnt.
   * - Erstellt einen neuen Block mit diesen Transaktionen.
   */
  public function minePendingTransactions(string $minerAddress): void {
    $pending = $this->ptdb->getAllTransactions();
    // Füge coinbase Transaktion hinzu
    $rewardTx = new Transaction("0", $minerAddress, $this->miningReward);
    $pending[] = $rewardTx;

    $newBlock = new Block(count($this->chain), $pending, $this->getLatestBlock()->hash);
    $newBlock->mineBlock($this->difficulty);
    $this->chain[] = $newBlock;
    $this->db->saveBlock($newBlock);
    // Leere den persistierten Transaktions-Pool
    $this->ptdb->clearTransactions();
  }

  public function getBalanceOfAddress(string $address): float {
    $balance = 0.0;
    foreach ($this->chain as $block) {
      if (!is_array($block->data)) {
        continue;
      }
      foreach ($block->data as $tx) {
        if (!$tx instanceof Transaction) {
          continue;
        }
        if ($tx->fromAddress === $address) {
          $balance -= $tx->amount;
        }
        if ($tx->toAddress === $address) {
          $balance += $tx->amount;
        }
      }
    }
    return $balance;
  }

  public function isChainValid(): bool {
    for ($i = 1; $i < count($this->chain); $i++) {
      $currentBlock = $this->chain[$i];
      $previousBlock = $this->chain[$i - 1];
      if ($currentBlock->hash !== $currentBlock->calculateHash()) {
        return false;
      }
      if ($currentBlock->previousHash !== $previousBlock->hash) {
        return false;
      }
    }
    return true;
  }
}
?>
