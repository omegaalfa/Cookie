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
    private static array $cookies = [];

    /**
     * Reset all cookies (useful for test isolation)
     */
    public static function reset(): void
    {
        self::$cookies = [];
    }

    public static function set(
        string $name,
        string $value,
        int|null $expiration = 0,
        string|null $path = "/",
        string|null $domain = "",
        bool|null $secure = false,
        bool|null $httpOnly = false,
        null|string $sameSite = null
    ): bool {
        $cookie = [
            'value'      => $value,
            'expiration' => $expiration,
            'path'       => $path,
            'domain'     => $domain,
            'secure'     => $secure,
            'httpOnly'   => $httpOnly,
            'sameSite'   => $sameSite,
        ];
        self::$cookies[$name] = $cookie;
        return true;
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

    public static function checkCookieConsent(): bool
    {
        return isset(self::$cookies['cookie_consent']) && self::$cookies['cookie_consent']['value'] === 'true';
    }

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
