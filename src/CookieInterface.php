<?php

declare(strict_types=1);

namespace omegaalfa\Cookie;

/**
 * Interface CookieInterface
 *
 * Defines the contract for cookie management operations.
 *
 * @package omegaalfa\Cookie
 */
interface CookieInterface
{
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
     */
    public static function configureEncryption(
        string $key,
        bool $encryptByDefault = false,
        array $except = []
    ): void;

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
        string $name,
        string $value,
        ?int $expiration = null,
        ?string $path = "/",
        ?string $domain = "",
        ?bool $secure = true,
        ?bool $httpOnly = true,
        ?string $sameSite = null
    ): bool;

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
    ): bool;

    /**
     * Set a cookie that lasts "forever" (400 days)
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
    ): bool;

    /**
     * Get cookie value
     *
     * @param string $name Cookie name
     * @param mixed $defaultValue Default value if cookie doesn't exist
     * @return mixed
     */
    public static function get(string $name, mixed $defaultValue = null): mixed;

    /**
     * Get and decrypt a cookie value
     *
     * @param string $name Cookie name
     * @param mixed $defaultValue Default value if cookie doesn't exist or decryption fails
     * @return mixed
     */
    public static function getDecrypted(string $name, mixed $defaultValue = null): mixed;

    /**
     * Check if a cookie exists
     *
     * @param string $name Cookie name
     * @return bool
     */
    public static function exists(string $name): bool;

    /**
     * Delete a cookie
     *
     * @param string $name Cookie name
     * @param string $path Path (must match original)
     * @param string $domain Domain (must match original)
     * @param bool $secure Secure flag (must match original)
     * @return bool
     */
    public static function delete(string $name, string $path = '', string $domain = '', bool $secure = false): bool;

    /**
     * Get all cookies for the current domain
     *
     * @return array
     */
    public static function getAllCookies(): array;

    /**
     * Delete all cookies for the current domain
     *
     * @return void
     */
    public static function clearAllCookies(): void;

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
    ): void;

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
    ): void;

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
    ): void;

    /**
     * Queue a cookie for deletion
     *
     * @param string $name Cookie name
     * @param string $path Path (must match original)
     * @param string $domain Domain (must match original)
     * @return void
     */
    public static function queueDelete(string $name, string $path = '/', string $domain = ''): void;

    /**
     * Remove a cookie from the queue
     *
     * @param string $name Cookie name
     * @return void
     */
    public static function unqueue(string $name): void;

    /**
     * Check if a cookie is queued
     *
     * @param string $name Cookie name
     * @return bool
     */
    public static function hasQueued(string $name): bool;

    /**
     * Get a queued cookie's data
     *
     * @param string $name Cookie name
     * @return array{value: string, options: array}|null
     */
    public static function getQueued(string $name): ?array;

    /**
     * Get all queued cookies
     *
     * @return array<string, array{value: string, options: array}>
     */
    public static function getAllQueued(): array;

    /**
     * Send all queued cookies
     *
     * @return bool
     */
    public static function sendQueued(): bool;

    /**
     * Clear the cookie queue without sending
     *
     * @return void
     */
    public static function flushQueue(): void;

    /**
     * Get the number of cookies in the queue
     *
     * @return int
     */
    public static function queueCount(): int;

    // =========================================================================
    // ENCRYPTION
    // =========================================================================

    /**
     * Encrypt a value using AES-256-GCM
     *
     * @param string $value Value to encrypt
     * @return string Base64 encoded encrypted value
     */
    public static function encrypt(string $value): string;

    /**
     * Decrypt a value
     *
     * @param string $encryptedValue Base64 encoded encrypted value
     * @return string|null Decrypted value or null if decryption fails
     */
    public static function decrypt(string $encryptedValue): ?string;

    // =========================================================================
    // CONSENT VERIFICATION
    // =========================================================================

    /**
     * Check if user has given consent to store cookies
     *
     * @return bool
     */
    public static function checkCookieConsent(): bool;

    // =========================================================================
    // REGEX OPERATIONS
    // =========================================================================

    /**
     * Get cookie values that match a regular expression
     *
     * @param string $regex Regular expression pattern
     * @return array
     */
    public static function getCookieValueByRegex(string $regex): array;

    /**
     * Delete all cookies that match a regular expression
     *
     * @param string $regex Regular expression pattern
     * @return bool
     */
    public static function deleteCookiesByRegex(string $regex): bool;

    // =========================================================================
    // OPTIONS BUILDER
    // =========================================================================

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
        ?int $expiration,
        ?string $path,
        ?string $domain,
        ?bool $secure = false,
        ?bool $httpOnly = false,
        ?string $sameSite = null
    ): array;
}
