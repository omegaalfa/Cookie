<?php

namespace omegaalfa\Cookie\tests;

use omegaalfa\Cookie\CookieInterface;

class MockCookieManagerTest implements CookieInterface
{
    private static array $cookies = [];

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
        // Simula a definição de um cookie
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
        $options = [
            'expires'  => $expiration,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ];

        foreach($options as $key => $option) {
            if(!$option) {
                unset($options[$key]);
            }
        }

        return $options;
    }

    public static function get(string $name, mixed $defaultValue = null): mixed
    {
        // Simula a obtenção de um cookie
        return self::$cookies[$name]['value'] ?? $defaultValue;
    }

    public static function delete(string $name, string $path = '', string $domain = '', bool $secure = false): bool
    {
        // Simula a deleção de um cookie
        unset(self::$cookies[$name]);
        return true;
    }

    public static function exists(string $name): bool
    {
        // Simula a verificação de existência de um cookie
        return isset(self::$cookies[$name]);
    }

    public static function isSecure(string $cookieName): bool
    {
        // Simula a verificação de segurança de um cookie
        return self::$cookies[$cookieName]['secure'] ?? false;
    }

    public static function isHttpOnly(string $cookieName): bool
    {
        // Simula a verificação de HTTP-only de um cookie
        return self::$cookies[$cookieName]['httpOnly'] ?? false;
    }

    public static function getExpirationTime(string $cookieName): ?int
    {
        // Simula a obtenção do tempo de expiração de um cookie
        return self::$cookies[$cookieName]['expiration'] ?? null;
    }

    public static function getDomain(string $cookieName): mixed
    {
        // Simula a obtenção do domínio de um cookie
        return self::$cookies[$cookieName]['domain'] ?? null;
    }

    public static function getPath(string $cookieName): ?string
    {
        // Simula a obtenção do caminho de um cookie
        return self::$cookies[$cookieName]['path'] ?? null;
    }

    public static function getAllCookies(): array
    {
        // Simula a obtenção de todos os cookies
        return self::$cookies;
    }

    public static function clearAllCookies(): void
    {
        // Simula a limpeza de todos os cookies
        self::$cookies = [];
    }

    public static function checkCookieConsent(): bool
    {
        // Simula a verificação de consentimento de cookies
        return isset(self::$cookies['cookie_consent']) && self::$cookies['cookie_consent']['value'] === 'true';
    }

    public static function getCookieValueByRegex(string $regex): array
    {
        // Simula a obtenção de valores de cookie que correspondem a uma expressão regular
        $matches = [];
        foreach(self::$cookies as $name => $cookie) {
            if(preg_match($regex, $name)) {
                $matches[] = $cookie['value'];
            }
        }
        return $matches;
    }

    public static function deleteCookiesByRegex(string $regex): bool
    {
        foreach(self::getAllCookies() as $cookieName => $cookieValue) {
            if(preg_match($regex, $cookieName) && !self::delete($cookieName)) {
                return false;
            }
        }

        return true;
    }
}
