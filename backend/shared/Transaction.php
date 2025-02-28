<?php
class Transaction implements \JsonSerializable {
    public string $fromAddress;
    public string $toAddress;
    public float $amount;
    public ?string $signature = null; // Wird später gesetzt
    public ?string $publicKey = null; // Der öffentliche Schlüssel des Senders

    public function __construct(string $fromAddress, string $toAddress, float $amount) {
        $this->fromAddress = $fromAddress;
        $this->toAddress   = $toAddress;
        $this->amount      = $amount;
    }

    // Berechnet einen Hash basierend auf den Transaktionsdaten
    public function calculateHash(): string {
        return hash('sha256', $this->fromAddress . $this->toAddress . $this->amount);
    }

    // Signiert die Transaktion mit dem privaten Schlüssel des Senders
    public function signTransaction(string $privateKey, string $publicKey): void {
        // "Säubere" den öffentlichen Schlüssel (entferne Leerzeichen/Zeilenumbrüche)
        $cleanPublicKey = trim($publicKey);
        // Überprüfen, ob der Absender mit dem öffentlichen Schlüssel übereinstimmt:
        if ($this->fromAddress !== hash('sha256', $cleanPublicKey)) {
            throw new Exception("You cannot sign transactions for other wallets!");
        }
        $hash = $this->calculateHash();
        if (!openssl_sign($hash, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new Exception("Signing failed: " . openssl_error_string());
        }
        $this->signature = base64_encode($signature);
        $this->publicKey = $cleanPublicKey;
    }

    // Überprüft, ob die Signatur gültig ist
    public function isValid(): bool {
        // Coinbase-Transaktionen (von "0") gelten als gültig
        if ($this->fromAddress === "0") return true;

        if (empty($this->signature) || empty($this->publicKey)) {
            throw new Exception("No signature in this transaction");
        }

        $hash = $this->calculateHash();
        $decodedSignature = base64_decode($this->signature);
        return openssl_verify($hash, $decodedSignature, $this->publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    public function jsonSerialize(): array {
        return [
            'fromAddress' => $this->fromAddress,
            'toAddress'   => $this->toAddress,
            'amount'      => $this->amount,
            'signature'   => $this->signature,
            'publicKey'   => $this->publicKey
        ];
    }
}
?>
