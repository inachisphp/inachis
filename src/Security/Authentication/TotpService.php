<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Security\Authentication;

/**
 * Provides TOTP (RFC 6238) secret generation and code verification.
 *
 * Uses HMAC-SHA1 with 6-digit codes and a 30-second time step.
 * A ±1 step verification window is allowed to compensate for clock skew.
 */
final class TotpService
{
    private const ALGORITHM = 'sha1';
    private const CODE_LENGTH = 6;
    private const TIME_STEP = 30;
    private const WINDOW = 1;

    /**
     * Base32 alphabet defined by RFC 4648.
     */
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a random Base32 encoded TOTP secret.
     *
     * 64 Base32 characters = 320 bits of entropy.
     *
     * @param int $length Base32 character length
     *
     * @return string
     */
    public function generateSecret(int $length = 64): string
    {
        if ($length % 8 !== 0) {
            throw new \InvalidArgumentException(
                'TOTP secret length must be a multiple of 8.'
            );
        }

        return $this->base32Encode(
            random_bytes((int) ($length * 5 / 8))
        );
    }

    /**
     * Verify a user supplied TOTP code.
     *
     * @param string $secret Base32 encoded secret
     * @param string $code   User supplied code
     * @return bool
     */
    public function verifyCode(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        if (
            $code === null ||
            strlen($code) !== self::CODE_LENGTH ||
            !ctype_digit($code)
        ) {
            return false;
        }

        $key = $this->base32Decode($secret);
        if ($key === '') {
            return false;
        }

        $currentStep = (int) floor(
            time() / self::TIME_STEP
        );

        for (
            $offset = -self::WINDOW;
            $offset <= self::WINDOW;
            $offset++
        ) {
            if (hash_equals(
                $this->generateCode(
                    $key,
                    $currentStep + $offset
                ),
                $code
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate the authenticator application provisioning URI.
     *
     * @param string $username
     * @param string $secret
     * @param string $issuer
     *
     * @return string
     */
    public function getProvisioningUri(
        string $username,
        string $secret,
        string $issuer = 'Inachis'
    ): string {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($username),
            $secret,
            rawurlencode($issuer),
            self::CODE_LENGTH,
            self::TIME_STEP
        );
    }

    /**
     * Generate a HOTP code for a counter.
     *
     * @param string $key
     * @param int $counter
     *
     * @return string
     */
    private function generateCode(
        string $key,
        int $counter
    ): string {
        $time = pack('N*', 0)
            . pack('N*', $counter);

        $hash = hash_hmac(
            self::ALGORITHM,
            $time,
            $key,
            true
        );

        $offset = ord(
            $hash[strlen($hash) - 1]
        ) & 0x0F;

        $binary =
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);

        return str_pad(
            (string) ($binary % (10 ** self::CODE_LENGTH)),
            self::CODE_LENGTH,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Encode binary data as Base32.
     *
     * @param string $data
     *
     * @return string
     */
    private function base32Encode(
        string $data
    ): string {
        $encoded = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (str_split($data) as $byte) {
            $buffer = ($buffer << 8) | ord($byte);
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;

                $encoded .= self::BASE32_CHARS[
                    ($buffer >> $bitsLeft) & 0x1F
                ];
            }
        }

        if ($bitsLeft > 0) {
            $encoded .= self::BASE32_CHARS[
                ($buffer << (5 - $bitsLeft)) & 0x1F
            ];
        }

        return $encoded;
    }

    /**
     * Decode Base32 data.
     *
     * @param string $encoded
     *
     * @return string
     */
    private function base32Decode(
        string $encoded
    ): string {
        $encoded = strtoupper(
            preg_replace('/\s+/', '', $encoded) ?? ''
        );

        $decoded = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (str_split($encoded) as $char) {
            $position = strpos(
                self::BASE32_CHARS,
                $char
            );

            if ($position === false) {
                throw new \InvalidArgumentException(
                    'Invalid Base32 TOTP secret.'
                );
            }

            $buffer = ($buffer << 5) | $position;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;

                $decoded .= chr(
                    ($buffer >> $bitsLeft) & 0xFF
                );
            }
        }

        return $decoded;
    }
}
