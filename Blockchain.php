<?php
require_once 'Block.php';
require_once 'BlockchainDB.php';

/**
 * Klasse zur Verwaltung der Blockchain
 */
class Blockchain {
  /** @var Block[] */
  public array $chain;
  public int $difficulty;
  private BlockchainDB $db;

  /**
   * Konstruktor: Initialisiert die Blockchain mit dem Genesis-Block.
   */
  public function __construct() {
    $this->db = new BlockchainDB();
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
        $block = new Block($row['idx'], json_decode($row['data'], true), $row['previous_hash']);
        $block->timestamp = (int)$row['timestamp'];
        $block->nonce = (int)$row['nonce'];
        $block->hash = $row['hash'];
        $this->chain[] = $block;
      }
    }
    $this->difficulty = 4;
  }

  /**
   * Erzeugt den Genesis-Block (erster Block der Kette)
   *
   * @return Block
   */
  private function createGenesisBlock(): Block {
    return new Block(0, "Genesis Block", "0");
  }

  /**
   * Gibt den zuletzt hinzugefügten Block zurück
   *
   * @return Block
   */
  public function getLatestBlock(): Block {
    return $this->chain[count($this->chain) - 1];
  }

  /**
   * Fügt einen neuen Block zur Kette hinzu, indem er zuerst gemined wird.
   *
   * @param Block $newBlock Der hinzuzufügende Block
   */
  public function addBlock(Block $newBlock): void {
    $newBlock->previousHash = $this->getLatestBlock()->hash;
    $newBlock->mineBlock($this->difficulty);
    $this->chain[] = $newBlock;
    $this->db->saveBlock($newBlock);
  }

  /**
   * Überprüft, ob die Blockchain gültig ist.
   *
   * @return bool True, wenn die Kette gültig ist, ansonsten false.
   */
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

