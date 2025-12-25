<?php

declare(strict_types=1);

namespace omegaalfa\Cookie;

use InvalidArgumentException;
use RuntimeException;

/**
 * Class Cookie
 *
 * Secure cookie management with encryption and queue support.
 *
 * @package omegaalfa\Cookie
 */
class Cookie implements CookieInterface
{
    // =========================================================================
    // CONSTANTS
    // =========================================================================

    private const DEFAULT_SAMESITE = 'Lax';
    private const CONSENT_COOKIE = 'cookie_consent';
    private const CONSENT_SIGNATURE_COOKIE = 'cookie_consent_signature';
    private const COOKIE_CONSENT_HMAC_KEY = 'cookie_consent:true';
    private const SAFE_REGEX_MAX_LENGTH = 255;
    private const CIPHER_METHOD = 'aes-256-gcm';
    private const FOREVER_MINUTES = 576000; // 400 days in minutes

    // =========================================================================
    // STATIC PROPERTIES
    // =========================================================================

    /**
     * @var array<string, array{value: string, options: array, encrypted?: bool, delete?: bool}>
     */
    private static array $queue = [];

    /**
     * @var string Encryption key (must be at least 32 bytes for AES-256)
     */
    private static string $encryptionKey = '';

    /**
     * @var bool Whether to encrypt all cookies by default
     */
    private static bool $encryptByDefault = false;

    /**
     * @var array<string> Cookie names excluded from automatic encryption
     */
    private static array $encryptionExcept = [];

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    /**
     * Configure encryption settings
     *
     * @param string $key The encryption key (must be at least 32 bytes for AES-256)
     * @param bool $encryptByDefault Whether to encrypt all cookies by default
     * @param array<string> $except Cookie names to exclude from automatic encryption
     * @return void
     * @throws InvalidArgumentException If key is less than 32 bytes
     */
    public static function configureEncryption(
        string $key,
        bool $encryptByDefault = false,
        array $except = []
    ): void {
        if (strlen($key) < 32) {
            throw new InvalidArgumentException('Encryption key must be at least 32 bytes');
        }
        self::$encryptionKey = $key;
        self::$encryptByDefault = $encryptByDefault;
        self::$encryptionExcept = $except;
    }

    // =========================================================================
    // BASIC COOKIE OPERATIONS
    // =========================================================================

    /**
     * Set a cookie with secure defaults
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int|null $expiration Unix timestamp for expiration (0 = session)
     * @param string|null $path Path where cookie is valid
     * @param string|null $domain Domain where cookie is valid
     * @param bool|null $secure Send only over HTTPS
     * @param bool|null $httpOnly Inaccessible via JavaScript
     * @param string|null $sameSite SameSite policy (Strict, Lax, None)
     * @return bool
     */
    public static function set(
        string      $name,
        string      $value,
        int|null    $expiration = 0,
        string|null $path = "/",
        string|null $domain = "",
        bool|null   $secure = true,
        bool|null   $httpOnly = true,
        string|null $sameSite = self::DEFAULT_SAMESITE
    ): bool {
        // Apply automatic encryption if configured
        if (self::$encryptByDefault && !in_array($name, self::$encryptionExcept, true)) {
            $value = self::encrypt($value);
        }

        $options = self::setCookieOptions(
            $expiration,
            $path,
            $domain,
            $secure,
            $httpOnly,
            $sameSite ?? self::DEFAULT_SAMESITE
        );

        return setcookie($name, $value, $options);
    }

    /**
     * Set a cookie with explicit encryption
     *
     * @param string $name Cookie name
     * @param string $value Cookie value (will be encrypted)
     * @param int|null $expiration Unix timestamp for expiration
     * @param string|null $path Path where cookie is valid
     * @param string|null $domain Domain where cookie is valid
     * @param bool|null $secure Send only over HTTPS
     * @param bool|null $httpOnly Inaccessible via JavaScript
     * @param string|null $sameSite SameSite policy
     * @return bool
     */
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
        
        // Temporarily disable auto-encryption to avoid double encryption
        $originalEncryptByDefault = self::$encryptByDefault;
        self::$encryptByDefault = false;
        
        $result = self::set($name, $encryptedValue, $expiration, $path, $domain, $secure, $httpOnly, $sameSite);
        
        self::$encryptByDefault = $originalEncryptByDefault;
        
        return $result;
    }

    /**
     * Set a cookie that lasts "forever" (400 days - browser maximum)
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param string|null $path Path where cookie is valid
     * @param string|null $domain Domain where cookie is valid
     * @param bool|null $secure Send only over HTTPS
     * @param bool|null $httpOnly Inaccessible via JavaScript
     * @param string|null $sameSite SameSite policy
     * @return bool
     */
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

    /**
     * Build cookie options array
     *
     * @param int|null $expiration Unix timestamp for expiration
     * @param string|null $path Path where cookie is valid
     * @param string|null $domain Domain where cookie is valid
     * @param bool|null $secure Send only over HTTPS
     * @param bool|null $httpOnly Inaccessible via JavaScript
     * @param string|null $sameSite SameSite policy
     * @return array
     */
    public static function setCookieOptions(
        int|null    $expiration,
        string|null $path,
        string|null $domain,
        bool|null   $secure = false,
        bool|null   $httpOnly = false,
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

    /**
     * Get cookie value
     *
     * @param string $name Cookie name
     * @param mixed $defaultValue Default value if cookie doesn't exist
     * @return mixed
     */
    public static function get(string $name, mixed $defaultValue = null): mixed
    {
        if (!self::exists($name)) {
            return $defaultValue;
        }

        return $_COOKIE[$name];
    }

    /**
     * Get and decrypt a cookie value
     *
     * @param string $name Cookie name
     * @param mixed $defaultValue Default value if cookie doesn't exist or decryption fails
     * @return mixed
     */
    public static function getDecrypted(string $name, mixed $defaultValue = null): mixed
    {
        $value = self::get($name);
        if ($value === null) {
            return $defaultValue;
        }

        $decrypted = self::decrypt($value);
        return $decrypted ?? $defaultValue;
    }

    /**
     * Check if a cookie exists
     *
     * @param string $name Cookie name
     * @return bool
     */
    public static function exists(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Delete a cookie
     *
     * @param string $name Cookie name
     * @param string $path Path (must match original)
     * @param string $domain Domain (must match original)
     * @param bool $secure Secure flag (must match original)
     * @return bool
     */
    public static function delete(string $name, string $path = '', string $domain = '', bool $secure = false): bool
    {
        if (array_key_exists($name, $_COOKIE)) {
            // @codeCoverageIgnoreStart
            if (false === setcookie($name, '', -1, $path, $domain, $secure)) {
                return false;
            }
            // @codeCoverageIgnoreEnd

            unset($_COOKIE[$name]);
        }

        return true;
    }

    /**
     * Get all cookies for the current domain
     *
     * @return array
     */
    public static function getAllCookies(): array
    {
        return $_COOKIE;
    }

    /**
     * Delete all cookies for the current domain
     *
     * @return void
     */
    public static function clearAllCookies(): void
    {
        foreach (self::getAllCookies() as $name => $value) {
            self::delete($name);
        }
    }

    // =========================================================================
    // QUEUE SYSTEM
    // =========================================================================

    /**
     * Add a cookie to the queue
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int|null $expiration Unix timestamp for expiration
     * @param string|null $path Path where cookie is valid
     * @param string|null $domain Domain where cookie is valid
     * @param bool|null $secure Send only over HTTPS
     * @param bool|null $httpOnly Inaccessible via JavaScript
     * @param string|null $sameSite SameSite policy
     * @return void
     */
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

    /**
     * Add an encrypted cookie to the queue
     *
     * @param string $name Cookie name
     * @param string $value Cookie value (will be encrypted)
     * @param int|null $expiration Unix timestamp for expiration
     * @param string|null $path Path where cookie is valid
     * @param string|null $domain Domain where cookie is valid
     * @param bool|null $secure Send only over HTTPS
     * @param bool|null $httpOnly Inaccessible via JavaScript
     * @param string|null $sameSite SameSite policy
     * @return void
     */
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

    /**
     * Add a "forever" cookie to the queue (400 days)
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param string|null $path Path where cookie is valid
     * @param string|null $domain Domain where cookie is valid
     * @param bool|null $secure Send only over HTTPS
     * @param bool|null $httpOnly Inaccessible via JavaScript
     * @param string|null $sameSite SameSite policy
     * @return void
     */
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

    /**
     * Queue a cookie for deletion
     *
     * @param string $name Cookie name
     * @param string $path Path (must match original)
     * @param string $domain Domain (must match original)
     * @return void
     */
    public static function queueDelete(string $name, string $path = '/', string $domain = ''): void
    {
        self::$queue[$name] = [
            'value' => '',
            'options' => self::setCookieOptions(time() - 3600, $path, $domain, false, false, null),
            'delete' => true,
        ];
    }

    /**
     * Remove a cookie from the queue
     *
     * @param string $name Cookie name
     * @return void
     */
    public static function unqueue(string $name): void
    {
        unset(self::$queue[$name]);
    }

    /**
     * Check if a cookie is queued
     *
     * @param string $name Cookie name
     * @return bool
     */
    public static function hasQueued(string $name): bool
    {
        return isset(self::$queue[$name]);
    }

    /**
     * Get a queued cookie's data
     *
     * @param string $name Cookie name
     * @return array{value: string, options: array}|null
     */
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

    /**
     * Get all queued cookies
     *
     * @return array<string, array{value: string, options: array}>
     */
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

    /**
     * Send all queued cookies
     *
     * @return bool
     */
    public static function sendQueued(): bool
    {
        $success = true;
        foreach (self::$queue as $name => $data) {
            if (isset($data['delete']) && $data['delete']) {
                if (!self::delete($name, $data['options']['path'] ?? '', $data['options']['domain'] ?? '')) {
                    // @codeCoverageIgnoreStart
                    $success = false;
                    // @codeCoverageIgnoreEnd
                }
            } else {
                if (!setcookie($name, $data['value'], $data['options'])) {
                    // @codeCoverageIgnoreStart
                    $success = false;
                    // @codeCoverageIgnoreEnd
                }
            }
        }
        self::flushQueue();
        return $success;
    }

    /**
     * Clear the cookie queue without sending
     *
     * @return void
     */
    public static function flushQueue(): void
    {
        self::$queue = [];
    }

    /**
     * Get the number of cookies in the queue
     *
     * @return int
     */
    public static function queueCount(): int
    {
        return count(self::$queue);
    }

    // =========================================================================
    // ENCRYPTION
    // =========================================================================

    /**
     * Encrypt a value using AES-256-GCM
     *
     * @param string $value Value to encrypt
     * @return string Base64 encoded encrypted value
     * @throws RuntimeException If encryption key not configured or encryption fails
     */
    public static function encrypt(string $value): string
    {
        if (self::$encryptionKey === '') {
            throw new RuntimeException('Encryption key not configured. Call configureEncryption() first.');
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
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Encryption failed');
            // @codeCoverageIgnoreEnd
        }

        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Decrypt a value
     *
     * @param string $encryptedValue Base64 encoded encrypted value
     * @return string|null Decrypted value or null if decryption fails
     * @throws RuntimeException If encryption key not configured
     */
    public static function decrypt(string $encryptedValue): ?string
    {
        if (self::$encryptionKey === '') {
            throw new RuntimeException('Encryption key not configured. Call configureEncryption() first.');
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

    /**
     * Check if user has given consent to store cookies
     *
     * @return bool
     */
    public static function checkCookieConsent(): bool
    {
        return self::hasSessionConsent() || self::hasValidConsentSignature();
    }

    // =========================================================================
    // REGEX OPERATIONS
    // =========================================================================

    /**
     * Get cookie values that match a regular expression
     *
     * @param string $regex Regular expression pattern
     * @return array
     * @throws InvalidArgumentException If regex is invalid or unsafe
     */
    public static function getCookieValueByRegex(string $regex): array
    {
        self::assertSafeRegex($regex);

        $matches = [];
        foreach ($_COOKIE as $name => $value) {
            if (self::matchesRegex($regex, $name)) {
                $matches[] = $value;
            }
        }

        return $matches;
    }

    /**
     * Delete all cookies that match a regular expression
     *
     * @param string $regex Regular expression pattern
     * @return bool
     * @throws InvalidArgumentException If regex is invalid or unsafe
     */
    public static function deleteCookiesByRegex(string $regex): bool
    {
        self::assertSafeRegex($regex);

        foreach (self::getAllCookies() as $cookieName => $cookieValue) {
            if (self::matchesRegex($regex, $cookieName) && !self::delete($cookieName)) {
                // @codeCoverageIgnoreStart
                return false;
                // @codeCoverageIgnoreEnd
            }
        }

        return true;
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Check if session has consent stored
     *
     * @return bool
     */
    private static function hasSessionConsent(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE
            && isset($_SESSION[self::CONSENT_COOKIE])
            && $_SESSION[self::CONSENT_COOKIE] === true;
    }

    /**
     * Check if cookie has valid HMAC signature for consent
     *
     * @return bool
     */
    private static function hasValidConsentSignature(): bool
    {
        $secret = self::getCookieConsentSecret();
        if (!$secret || !isset($_COOKIE[self::CONSENT_COOKIE], $_COOKIE[self::CONSENT_SIGNATURE_COOKIE])) {
            return false;
        }

        if ($_COOKIE[self::CONSENT_COOKIE] !== 'true') {
            return false;
        }

        $expected = hash_hmac('sha256', self::COOKIE_CONSENT_HMAC_KEY, $secret);

        return hash_equals($expected, $_COOKIE[self::CONSENT_SIGNATURE_COOKIE]);
    }

    /**
     * Get the cookie consent secret from environment
     *
     * @return string|null
     */
    private static function getCookieConsentSecret(): ?string
    {
        $secret = $_ENV['COOKIE_CONSENT_SECRET'] ?? $_SERVER['COOKIE_CONSENT_SECRET'] ?? getenv('COOKIE_CONSENT_SECRET');

        return is_string($secret) && $secret !== '' ? $secret : null;
    }

    /**
     * Assert that a regex pattern is safe to execute
     *
     * @param string $regex
     * @return void
     * @throws InvalidArgumentException If regex is unsafe
     */
    private static function assertSafeRegex(string $regex): void
    {
        if ($regex === '') {
            throw new InvalidArgumentException('Regex não pode ser vazio');
        }

        if (strlen($regex) > self::SAFE_REGEX_MAX_LENGTH) {
            throw new InvalidArgumentException('Regex muito longo');
        }

        if (!preg_match('/^(.)(.*)\\1[imsxuADSUXJ]*$/', $regex)) {
            throw new InvalidArgumentException('Regex deve usar delimitador válido');
        }

        if (preg_match('/(\.\*){2,}/', $regex) || preg_match('/[\*\+\?]{3,}/', $regex) || preg_match('/\(\?/', $regex)) {
            throw new InvalidArgumentException('Regex não permitido por razões de segurança');
        }
    }

    /**
     * Check if a string matches a regex pattern
     *
     * @param string $regex
     * @param string $name
     * @return bool
     * @throws InvalidArgumentException If regex execution fails
     */
    private static function matchesRegex(string $regex, string $name): bool
    {
        $result = @preg_match($regex, $name);

        if ($result === false || preg_last_error() !== PREG_NO_ERROR) {
            throw new InvalidArgumentException('Erro ao executar regex');
        }

        return $result === 1;
    }
}
