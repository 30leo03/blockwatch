<?php
session_start();
require_once __DIR__ . '/backend/wallet/WalletDB.php';
require_once __DIR__ . '/backend/wallet/Wallet.php';

// Falls das Formular abgeschickt wurde:
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $address = $_POST["address"] ?? "";
    $privateKeyInput = $_POST["privateKey"] ?? "";

    $walletDB = new WalletDB();
    // Wir gehen davon aus, dass WalletDB eine Methode getWalletByAddress() hat.
    $walletData = $walletDB->getWalletByAddress($address);

    if ($walletData) {
        // Erzeuge das Wallet-Objekt aus den persistierten Daten.
        $wallet = Wallet::fromArray($walletData);
        // Vergleiche den gespeicherten privaten Schlüssel (mit trim) mit dem eingegebenen.
        if (trim($wallet->privateKey) === trim($privateKeyInput)) {
            // Authentifizierung erfolgreich – speichere Wallet in der Session
            $_SESSION["wallet"] = $walletData;
            header("Location: index.php"); // Weiterleitung an dein Hauptinterface
            exit;
        } else {
            $error = "Falscher privater Schlüssel.";
        }
    } else {
        $error = "Wallet nicht gefunden.";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Wallet Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { background: #fff; padding: 20px; border-radius: 8px; max-width: 500px; margin: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
        input[type="submit"] { background: #4CAF50; border: none; color: #fff; cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <h1>Wallet Login</h1>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post" action="">
        <label>Wallet-Adresse:</label>
        <input type="text" name="address" required>
        <label>Privater Schlüssel:</label>
        <textarea name="privateKey" required></textarea>
        <input type="submit" value="Login">
    </form>
</div>
</body>
</html>
