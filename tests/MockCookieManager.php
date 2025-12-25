<?php

declare(strict_types=1);

namespace omegaalfa\Cookie\tests;

use omegaalfa\Cookie\CookieInterface;

/**
 * Mock implementation of CookieInterface for testing purposes.
 * This class simulates cookie operations in memory without requiring
 * actual HTTP headers to be sent.
 */
class MockCookieManager implements CookieInterface
{
    private const CIPHER_METHOD = 'aes-256-gcm';
    private const FOREVER_MINUTES = 576000; // 400 days in minutes

    private static array $cookies = [];
    private static array $queue = [];
    private static string $encryptionKey = '';
    private static bool $encryptByDefault = false;
    private static array $encryptionExcept = [];

    /**
     * Reset all cookies and queue (useful for test isolation)
     */
    public static function reset(): void
    {
        self::$cookies = [];
        self::$queue = [];
        self::$encryptionKey = '';
        self::$encryptByDefault = false;
        self::$encryptionExcept = [];
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    public static function configureEncryption(
        string $key,
        bool $encryptByDefault = false,
        array $except = []
    ): void {
        if (strlen($key) < 32) {
            throw new \InvalidArgumentException('Encryption key must be at least 32 bytes');
        }
        self::$encryptionKey = $key;
        self::$encryptByDefault = $encryptByDefault;
        self::$encryptionExcept = $except;
    }

    // =========================================================================
    // BASIC COOKIE OPERATIONS
    // =========================================================================

    public static function set(
        string $name,
        string $value,
        int|null $expiration = null,
        string|null $path = "/",
        string|null $domain = "",
        bool|null $secure = true,
        bool|null $httpOnly = true,
        null|string $sameSite = null
    ): bool {
        // Apply automatic encryption if configured
        if (self::$encryptByDefault && !in_array($name, self::$encryptionExcept, true)) {
            $value = self::encrypt($value);
        }

        $cookie = [
            'value'      => $value,
            'expiration' => $expiration ?? 0,
            'path'       => $path,
            'domain'     => $domain,
            'secure'     => $secure,
            'httpOnly'   => $httpOnly,
            'sameSite'   => $sameSite,
        ];
        self::$cookies[$name] = $cookie;
        return true;
    }

    public static function setEncrypted(
        string $name,
        string $value,
        ?int $expiration = null,
        ?string $path = "/",
        ?string $domain = "",
        ?bool $secure = true,
        ?bool $httpOnly = true,
        ?string $sameSite = null
    ): bool {
        $encryptedValue = self::encrypt($value);
        return self::set($name, $encryptedValue, $expiration, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public static function forever(
        string $name,
        string $value,
        ?string $path = "/",
        ?string $domain = "",
        ?bool $secure = true,
        ?bool $httpOnly = true,
        ?string $sameSite = null
    ): bool {
        $expiration = time() + (self::FOREVER_MINUTES * 60);
        return self::set($name, $value, $expiration, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public static function setCookieOptions(
        int|null $expiration,
        string|null $path,
        string|null $domain,
        bool|null $secure = false,
        bool|null $httpOnly = false,
        null|string $sameSite = null
    ): array {
        $options = [];

        if ($expiration !== null) {
            $options['expires'] = $expiration;
        }
        if ($path !== null) {
            $options['path'] = $path;
        }
        if ($domain !== null && $domain !== '') {
            $options['domain'] = $domain;
        }
        if ($secure !== null) {
            $options['secure'] = $secure;
        }
        if ($httpOnly !== null) {
            $options['httponly'] = $httpOnly;
        }
        if ($sameSite !== null) {
            $options['samesite'] = $sameSite;
        }

        return $options;
    }

    public static function get(string $name, mixed $defaultValue = null): mixed
    {
        return self::$cookies[$name]['value'] ?? $defaultValue;
    }

    public static function getDecrypted(string $name, mixed $defaultValue = null): mixed
    {
        $value = self::get($name);
        if ($value === null) {
            return $defaultValue;
        }

        $decrypted = self::decrypt($value);
        return $decrypted ?? $defaultValue;
    }

    public static function delete(string $name, string $path = '', string $domain = '', bool $secure = false): bool
    {
        unset(self::$cookies[$name]);
        return true;
    }

    public static function exists(string $name): bool
    {
        return isset(self::$cookies[$name]);
    }

    public static function getAllCookies(): array
    {
        return self::$cookies;
    }

    public static function clearAllCookies(): void
    {
        self::$cookies = [];
    }

    // =========================================================================
    // QUEUE SYSTEM
    // =========================================================================

    public static function queue(
        string $name,
        string $value,
        ?int $expiration = null,
        ?string $path = "/",
        ?string $domain = "",
        ?bool $secure = true,
        ?bool $httpOnly = true,
        ?string $sameSite = null
    ): void {
        self::$queue[$name] = [
            'value' => $value,
            'options' => self::setCookieOptions($expiration, $path, $domain, $secure, $httpOnly, $sameSite),
            'encrypted' => false,
        ];
    }

    public static function queueEncrypted(
        string $name,
        string $value,
        ?int $expiration = null,
        ?string $path = "/",
        ?string $domain = "",
        ?bool $secure = true,
        ?bool $httpOnly = true,
        ?string $sameSite = null
    ): void {
        self::$queue[$name] = [
            'value' => self::encrypt($value),
            'options' => self::setCookieOptions($expiration, $path, $domain, $secure, $httpOnly, $sameSite),
            'encrypted' => true,
        ];
    }

    public static function queueForever(
        string $name,
        string $value,
        ?string $path = "/",
        ?string $domain = "",
        ?bool $secure = true,
        ?bool $httpOnly = true,
        ?string $sameSite = null
    ): void {
        $expiration = time() + (self::FOREVER_MINUTES * 60);
        self::queue($name, $value, $expiration, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public static function queueDelete(string $name, string $path = '/', string $domain = ''): void
    {
        self::$queue[$name] = [
            'value' => '',
            'options' => self::setCookieOptions(time() - 3600, $path, $domain, false, false, null),
            'delete' => true,
        ];
    }

    public static function unqueue(string $name): void
    {
        unset(self::$queue[$name]);
    }

    public static function hasQueued(string $name): bool
    {
        return isset(self::$queue[$name]);
    }

    public static function getQueued(string $name): ?array
    {
        if (!isset(self::$queue[$name])) {
            return null;
        }

        return [
            'value' => self::$queue[$name]['value'],
            'options' => self::$queue[$name]['options'],
        ];
    }

    public static function getAllQueued(): array
    {
        $result = [];
        foreach (self::$queue as $name => $data) {
            $result[$name] = [
                'value' => $data['value'],
                'options' => $data['options'],
            ];
        }
        return $result;
    }

    public static function sendQueued(): bool
    {
        foreach (self::$queue as $name => $data) {
            if (isset($data['delete']) && $data['delete']) {
                self::delete($name);
            } else {
                $options = $data['options'];
                self::$cookies[$name] = [
                    'value' => $data['value'],
                    'expiration' => $options['expires'] ?? 0,
                    'path' => $options['path'] ?? '/',
                    'domain' => $options['domain'] ?? '',
                    'secure' => $options['secure'] ?? false,
                    'httpOnly' => $options['httponly'] ?? false,
                    'sameSite' => $options['samesite'] ?? null,
                ];
            }
        }
        self::flushQueue();
        return true;
    }

    public static function flushQueue(): void
    {
        self::$queue = [];
    }

    public static function queueCount(): int
    {
        return count(self::$queue);
    }

    // =========================================================================
    // ENCRYPTION
    // =========================================================================

    public static function encrypt(string $value): string
    {
        if (empty(self::$encryptionKey)) {
            throw new \RuntimeException('Encryption key not configured. Call configureEncryption() first.');
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $tag = '';

        $encrypted = openssl_encrypt(
            $value,
            self::CIPHER_METHOD,
            self::$encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $tag . $encrypted);
    }

    public static function decrypt(string $encryptedValue): ?string
    {
        if (empty(self::$encryptionKey)) {
            throw new \RuntimeException('Encryption key not configured. Call configureEncryption() first.');
        }

        $data = base64_decode($encryptedValue, true);
        if ($data === false) {
            return null;
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
        if (strlen($data) < $ivLength + 16) {
            return null;
        }

        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, 16);
        $encrypted = substr($data, $ivLength + 16);

        $decrypted = openssl_decrypt(
            $encrypted,
            self::CIPHER_METHOD,
            self::$encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $decrypted === false ? null : $decrypted;
    }

    // =========================================================================
    // CONSENT VERIFICATION
    // =========================================================================

    public static function checkCookieConsent(): bool
    {
        return isset(self::$cookies['cookie_consent']) && self::$cookies['cookie_consent']['value'] === 'true';
    }

    // =========================================================================
    // REGEX OPERATIONS
    // =========================================================================

    public static function getCookieValueByRegex(string $regex): array
    {
        $matches = [];
        foreach (self::$cookies as $name => $cookie) {
            if (preg_match($regex, $name)) {
                $matches[] = $cookie['value'];
            }
        }
        return $matches;
    }

    public static function deleteCookiesByRegex(string $regex): bool
    {
        foreach (self::getAllCookies() as $cookieName => $cookieValue) {
            if (preg_match($regex, $cookieName) && !self::delete($cookieName)) {
                return false;
            }
        }

        return true;
    }
}
