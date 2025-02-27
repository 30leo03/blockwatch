<?php
require_once 'Blockchain.php';

// Erzeuge eine neue Blockchain
$myBlockchain = new Blockchain();

// Füge einige Beispiel-Blöcke hinzu
echo "Mining Block 1...\n\n";
$myBlockchain->addBlock(new Block(1, ["amount" => 100, "sender" => "Alice", "receiver" => "Bob"]));

echo "Mining Block 2...\n";
$myBlockchain->addBlock(new Block(2, ["amount" => 50, "sender" => "Bob", "receiver" => "Charlie"]));

echo "Mining Block 3...\n";
$myBlockchain->addBlock(new Block(3, ["amount" => 740, "sender" => "Peter", "receiver" => "Justus"]));

// Ausgabe der gesamten Blockchain
echo "<pre>";
print_r($myBlockchain);
echo "</pre>";

// Überprüfe die Integrität der Blockchain
if ($myBlockchain->isChainValid()) {
  echo "Die Blockchain ist gültig!";
} else {
  echo "Die Blockchain ist nicht gültig!";
}
?>
