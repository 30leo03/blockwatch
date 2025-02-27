<?php
require_once 'Block.php';

/**
 * Klasse zur Verwaltung der Blockchain
 */
class Blockchain {
  /** @var Block[] */
  public array $chain;
  public int $difficulty;

  /**
   * Konstruktor: Initialisiert die Blockchain mit dem Genesis-Block.
   */
  public function __construct() {
    $this->chain = [$this->createGenesisBlock()];
    $this->difficulty = 4; // Legt den Mining-Schwierigkeitsgrad fest
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

      // Prüfe, ob der aktuelle Hash noch korrekt ist
      if ($currentBlock->hash !== $currentBlock->calculateHash()) {
        return false;
      }
      // Prüfe, ob der Verweis auf den vorherigen Block stimmt
      if ($currentBlock->previousHash !== $previousBlock->hash) {
        return false;
      }
    }
    return true;
  }
}
?>
