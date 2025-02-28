<?php
require_once __DIR__ . '/../wallet/Wallet.php';
require_once __DIR__ . '/../wallet/WalletDB.php';
require_once __DIR__ . '/../chain/Blockchain.php';
require_once __DIR__ . '/../shared/Transaction.php';

class ActionController {
    private Blockchain $blockchain;
    /** @var array — gespeicherte Wallets als assoziative Arrays aus der DB */
    private array $wallets;
    private WalletDB $walletDB;

    public function __construct() {
        $this->blockchain = new Blockchain();
        $this->walletDB = new WalletDB();
        // Lade bereits gespeicherte Wallets aus der DB
        $this->wallets = $this->walletDB->getWallets();
    }

    /**
     * Startet das Web-Interface.
     */
    public function runWeb(): void {
        echo "<!DOCTYPE html>
<html lang='de'>
<head>
  <meta charset='UTF-8'>
  <title>Blockchain Web Interface</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
    .container { background: #fff; padding: 20px; border-radius: 8px; max-width: 900px; margin: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    h1, h2, h3 { color: #333; }
    a.button { display: inline-block; padding: 10px 20px; background: #2196F3; color: #fff; text-decoration: none; border-radius: 4px; margin: 5px; transition: background 0.3s; }
    a.button:hover { background: #1976D2; }
    .menu { margin-top: 20px; display: flex; flex-wrap: wrap; gap: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #f0f0f0; }
    form { margin-top: 20px; max-width: 500px; }
    input[type='text'], input[type='number'] { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
    input[type='submit'] { padding: 10px 20px; background: #4CAF50; border: none; color: #fff; border-radius: 4px; cursor: pointer; transition: background 0.3s; }
    input[type='submit']:hover { background: #45A049; }
    pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto; }
    hr { border: none; border-top: 1px solid #eee; margin-top: 20px; margin-bottom: 20px; }
  </style>
</head>
<body>
<div class='container'>
  <h1>Blockchain Web Interface</h1>";

        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'create_wallet':
                $this->createWallet();
                break;
            case 'create_transaction':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $from = $_POST['from'] ?? '';
                    $to = $_POST['to'] ?? '';
                    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
                    $this->createTransaction($from, $to, $amount);
                } else {
                    $this->showTransactionForm();
                }
                break;
            case 'mine':
                $this->mineTransactions();
                break;
            case 'display_balances':
                $this->displayBalances();
                break;
            case 'display_blockchain':
                $this->displayBlockchain();
                break;
            case 'validate':
                $this->validateBlockchain();
                break;
            default:
                echo "<p>Willkommen im Blockchain Web Interface. Bitte wählen Sie eine Aktion.</p>";
                break;
        }

        $this->showMenu();

        echo "</div></body></html>";
    }

    /**
     * Erzeugt ein neues Wallet, speichert es in der DB und zeigt die Adresse.
     */
    private function createWallet(): void {
        $wallet = new Wallet();
        $this->walletDB->saveWallet($wallet);
        // Nach dem Speichern die Wallet-Liste neu laden
        $this->wallets = $this->walletDB->getWallets();
        echo "<h2>Neues Wallet erstellt</h2>";
        echo "<p><strong>Adresse:</strong> {$wallet->address}</p>";
        echo "<p><strong>Öffentlicher Schlüssel:</strong><br><pre>" . htmlspecialchars($wallet->publicKey) . "</pre></p>";
    }

    /**
     * Zeigt ein Formular, um eine Transaktion zu erstellen.
     */
    private function showTransactionForm(): void {
        echo "<h2>Neue Transaktion erstellen</h2>";
        echo "<form method='post' action='?action=create_transaction'>
                <label for='from'>Absenderadresse:</label><br>
                <input type='text' name='from' id='from' required><br>
                <label for='to'>Empfängeradresse:</label><br>
                <input type='text' name='to' id='to' required><br>
                <label for='amount'>Betrag:</label><br>
                <input type='number' name='amount' id='amount' step='0.01' required><br><br>
                <input type='submit' value='Transaktion erstellen'>
              </form>";
    }

    /**
     * Fügt eine neue Transaktion hinzu, wobei überprüft wird, ob der Absender tatsächlich der Eigentümer des Wallets ist.
     */
    private function createTransaction(string $from, string $to, float $amount): void {
        // Suche in der persistierten Wallet-Liste nach dem Wallet-Objekt, das zum Absender gehört.
        $walletObj = null;
        foreach ($this->wallets as $wData) {
            if ($wData['address'] === $from) {
                $walletObj = Wallet::fromArray($wData);
                break;
            }
        }
        if (!$walletObj) {
            echo "<p style='color:red;'>Kein Wallet gefunden oder Sie besitzen dieses Wallet nicht.</p>";
            return;
        }
        // Das Wallet-Objekt wird verwendet, um die Transaktion zu signieren.
        try {
            $transaction = $walletObj->createSignedTransaction($to, $amount);
            $this->blockchain->createTransaction($transaction);
            echo "<p>Transaktion von <strong>{$walletObj->address}</strong> nach <strong>$to</strong> über <strong>$amount</strong> wurde erstellt.</p>";
        } catch (Exception $e) {
            echo "<p style='color:red;'>Fehler: " . $e->getMessage() . "</p>";
        }
    }

    /**
     * Führt das Mining der ausstehenden Transaktionen durch.
     */
    private function mineTransactions(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $miner = $_POST['miner'] ?? 'StandardMiner';
            $this->blockchain->minePendingTransactions($miner);
            echo "<p>Mining abgeschlossen. Miner <strong>$miner</strong> wurde belohnt.</p>";
        } else {
            echo "<h2>Mining ausführen</h2>";
            echo "<form method='post' action='?action=mine'>
                    <label for='miner'>Miner-Adresse (Zieladresse für Rewards):</label><br>
                    <input type='text' name='miner' id='miner' required placeholder='Gib deine Wallet-Adresse ein'><br><br>
                    <input type='submit' value='Mining starten'>
                  </form>";
        }
    }

    /**
     * Zeigt die Kontostände aller in der Wallet-Datenbank gespeicherten Wallets an.
     */
    private function displayBalances(): void {
        echo "<h2>Kontostände</h2>";
        if (empty($this->wallets)) {
            echo "<p>Keine Wallets vorhanden.</p>";
            return;
        }
        echo "<table>
                <tr>
                  <th>Adresse</th>
                  <th>Balance</th>
                </tr>";
        foreach ($this->wallets as $walletData) {
            $address = $walletData['address'];
            $balance = $this->blockchain->getBalanceOfAddress($address);
            echo "<tr>
                    <td>$address</td>
                    <td>$balance</td>
                  </tr>";
        }
        echo "</table>";
    }

    /**
     * Zeigt den Inhalt der Blockchain an.
     */
    private function displayBlockchain(): void {
        echo "<h2>Blockchain Inhalt</h2>";
        echo "<pre>" . htmlspecialchars(print_r($this->blockchain, true)) . "</pre>";
    }

    /**
     * Prüft die Blockchain und zeigt das Ergebnis an.
     */
    private function validateBlockchain(): void {
        echo "<h2>Blockchain Validierung</h2>";
        if ($this->blockchain->isChainValid()) {
            echo "<p style='color:green;'><strong>Die Blockchain ist gültig!</strong></p>";
        } else {
            echo "<p style='color:red;'><strong>Die Blockchain ist nicht gültig!</strong></p>";
        }
    }

    /**
     * Zeigt ein Menü mit Links zu den verschiedenen Aktionen.
     */
    private function showMenu(): void {
        echo "<hr>
          <h3>Menü</h3>
          <div class='menu'>
            <a class='button' href='?action=create_wallet'>Neues Wallet erstellen</a>
            <a class='button' href='?action=create_transaction'>Transaktion erstellen</a>
            <a class='button' href='?action=mine'>Mining ausführen</a>
            <a class='button' href='?action=display_balances'>Kontostände anzeigen</a>
            <a class='button' href='?action=display_blockchain'>Blockchain anzeigen</a>
            <a class='button' href='?action=validate'>Blockchain validieren</a>
            <a class='button' href='index.php'>Startseite</a>
          </div>";
    }
}
?>
