<?php
class Wallet {
    public string $privateKey;
    public string $publicKey;
    public string $address;

    public function __construct() {
        $this->generateKeys();
    }

    private function generateKeys(): void {
        $config = [
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privateKey);
        $this->privateKey = $privateKey;
        $details = openssl_pkey_get_details($res);
        $this->publicKey = $details["key"];
        // Adresse als Hash des öffentlichen Schlüssels
        $this->address = hash('sha256', $this->publicKey);
    }

    // Erstellt ein Wallet-Objekt aus einem assoziativen Array (z. B. aus der DB)
    public static function fromArray(array $data): Wallet {
        $wallet = new self();
        // Überschreibe die generierten Werte mit den gespeicherten
        $wallet->address = $data['address'];
        $wallet->publicKey = trim($data['public_key']); // Mit trim(), falls nötig
        $wallet->privateKey = $data['private_key'];
        return $wallet;
    }

    // Erstellt und signiert eine Transaktion
    public function createSignedTransaction(string $toAddress, float $amount): Transaction {
        $tx = new Transaction($this->address, $toAddress, $amount);
        $tx->signTransaction($this->privateKey, $this->publicKey);
        return $tx;
    }
}
?>
