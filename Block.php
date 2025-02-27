<?php
/**
 * Klasse für einen einzelnen Block in der Blockchain
 */
class Block {
  public int $index;
  public int $timestamp;
  public $data; // kann beliebige Transaktionsdaten enthalten
  public string $previousHash;
  public string $hash;
  public int $nonce;

  /**
   * Konstruktor für einen neuen Block
   *
   * @param int    $index        Position des Blocks in der Kette
   * @param mixed  $data         Transaktions- oder sonstige Daten
   * @param string $previousHash Hash des vorherigen Blocks (Standard: leerer String)
   */
  public function __construct(int $index, $data, string $previousHash = '') {
    $this->index = $index;
    $this->timestamp = time();
    $this->data = $data;
    $this->previousHash = $previousHash;
    $this->nonce = 0;
    $this->hash = $this->calculateHash();
  }

  /**
   * Berechnet den Hash des Blocks aus seinen Eigenschaften
   *
   * @return string Der berechnete SHA-256 Hash
   */
  public function calculateHash(): string {
    return hash('sha256', $this->index . $this->timestamp . json_encode($this->data) . $this->previousHash . $this->nonce);
  }

  /**
   * Simuliert den Mining-Prozess: Es wird solange der Nonce verändert,
   * bis der Hash mit der geforderten Anzahl führender Nullen (Difficulty) übereinstimmt.
   *
   * @param int $difficulty Anzahl der führenden Nullen, die der Hash haben muss
   */
  public function mineBlock(int $difficulty): void {
    $target = str_repeat("0", $difficulty);
    while (substr($this->hash, 0, $difficulty) !== $target) {
      $this->nonce++;
      $this->hash = $this->calculateHash();
    }
    echo "Block mined: " . $this->hash . "\n";
  }
}
?>
