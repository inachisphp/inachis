<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service;

use RuntimeException;

/**
 * Service for handling encryption and decryption operations.
 */
class EncryptionService
{
    /** @var string The master key used for wrapping row keys */
    private readonly string $masterKey;

    /**
     * Construct the encryption service with the master key
     *
     * @param string $masterKey
     */
    public function __construct(string $masterKey)
    {
        if (str_starts_with($masterKey, 'base64:')) {
            $masterKey = base64_decode(substr($masterKey, 7), true);
        }

        if ($masterKey === false) {
            throw new RuntimeException('Unable to decode master key.');
        }

        if (strlen($masterKey) !== 32) {
            throw new RuntimeException(
                sprintf(
                    'Master key must be 32 bytes, got %d bytes.',
                    strlen($masterKey)
                )
            );
        }

        $this->masterKey = $masterKey;
    }

    /**
     * Generate a random AES-256 row key.
     *
     * @return string The generated row key
     */
    public function generateRowKey(): string
    {
        return random_bytes(32);
    }

    /**
     * Encrypt a row key using the master key.
     *
     * @param string $rowKey The plaintext row key to encrypt
     * @return string The base64-encoded wrapped key
     */
    public function wrapKey(string $rowKey): string
    {
        $iv = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $rowKey,
            'aes-256-gcm',
            $this->masterKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Failed to wrap row key.');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt a wrapped row key.
     *
     * @param string $wrapped The base64-encoded wrapped key
     * @return string The decrypted row key
     */
    public function unwrapKey(string $wrapped): string
    {
        $data = base64_decode($wrapped, true);

        if ($data === false) {
            throw new RuntimeException('Invalid wrapped key.');
        }

        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        $rowKey = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->masterKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($rowKey === false) {
            throw new RuntimeException('Failed to unwrap row key.');
        }

        return $rowKey;
    }

    /**
     * Encrypt a value using a row key.
     *
     * @param string $plaintext The plaintext value to encrypt
     * @param string $rowKey The decrypted row key to use for encryption
     * @return string The base64-encoded encrypted value
     */
    public function encryptValue(string $plaintext, string $rowKey): string
    {
        $iv = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $rowKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Failed to encrypt value.');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt a value using a row key
     *
     * @param string $encryptedValue The base64-encoded encrypted value
     * @param string $rowKey The decrypted row key to use for decryption
     * @return string The decrypted plaintext value
     */
    public function decryptValue(string $encryptedValue, string $rowKey): string
    {
        $data = base64_decode($encryptedValue, true);

        if ($data === false) {
            throw new RuntimeException('Invalid encrypted value.');
        }

        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $rowKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Failed to decrypt value.');
        }

        return $plaintext;
    }
}
