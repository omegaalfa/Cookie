<?php

declare(strict_types=1);

namespace omegaalfa\Cookie;

use InvalidArgumentException;

/**
 * Class Cookie
 *
 * @package src\classes
 */
class Cookie implements CookieInterface
{
    private const DEFAULT_SAMESITE = 'Lax';
    private const CONSENT_COOKIE = 'cookie_consent';
    private const CONSENT_SIGNATURE_COOKIE = 'cookie_consent_signature';
    private const COOKIE_CONSENT_HMAC_KEY = 'cookie_consent:true';
    private const SAFE_REGEX_MAX_LENGTH = 255;

    /**
     *  Ex. Cookie::set('theme', 'red');
     *  setcookie('SID', '31d4d96e407aad42', time() + 3600, '/~rasmus/', 'example.com', true, true, 'Strict');
     *
     * @param string $name
     * @param string $value
     * @param int|null $expiration
     * @param string|null $path
     * @param string|null $domain
     * @param bool|null $secure
     * @param bool|null $httpOnly
     * @param string|null $sameSite
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
    ): bool
    {
        $options = self::setCookieOptions($expiration, $path, $domain, $secure, $httpOnly, $sameSite ?? self::DEFAULT_SAMESITE);

        return setcookie($name, $value, $options);
    }

    /**
     * @param int|null $expiration
     * @param string|null $path
     * @param string|null $domain
     * @param bool|null $secure
     * @param bool|null $httpOnly
     * @param string|null $sameSite
     * @return array
     */
    public static function setCookieOptions(
        int|null    $expiration,
        string|null $path,
        string|null $domain,
        bool|null   $secure = false,
        bool|null   $httpOnly = false,
        null|string $sameSite = null
    ): array
    {
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
     * @param string $name
     * @param null $defaultValue
     *
     * @return mixed
     *
     * NOTE: o valor retornado é bruto; quem exibir em HTML deve escapar com htmlspecialchars().
     */
    public static function get(string $name, $defaultValue = null): mixed
    {
        if (!self::exists($name)) {
            return $defaultValue;
        }

        return $_COOKIE[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function exists(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Deletes all cookies set for the current domain
     *
     * @return void
     */
    public static function clearAllCookies(): void
    {
        foreach (self::getAllCookies() as $name => $value) {
            self::delete($name);
        }
    }

    /**
     * Returns an array of all cookies set for the current domain
     *
     * @return array
     */
    public static function getAllCookies(): array
    {
        return $_COOKIE;
    }

    /**
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool $secure
     *
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
     * Checks if the user has given consent to store cookies
     *
     * @return bool
     */
    public static function checkCookieConsent(): bool
    {
        return self::hasSessionConsent() || self::hasValidConsentSignature();
    }

    /**
     * Returns an array of cookie values that match a given regular expression
     *
     * @param string $regex
     *
     * @return array
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
     * Deletes all cookies that match a given regular expression
     *
     * @param string $regex
     *
     * @return bool
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

    private static function hasSessionConsent(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE
            && isset($_SESSION[self::CONSENT_COOKIE])
            && $_SESSION[self::CONSENT_COOKIE] === true;
    }

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

    private static function getCookieConsentSecret(): ?string
    {
        $secret = $_ENV['COOKIE_CONSENT_SECRET'] ?? $_SERVER['COOKIE_CONSENT_SECRET'] ?? getenv('COOKIE_CONSENT_SECRET');

        return is_string($secret) && $secret !== '' ? $secret : null;
    }

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

    private static function matchesRegex(string $regex, string $name): bool
    {
        $result = @preg_match($regex, $name);

        if ($result === false || preg_last_error() !== PREG_NO_ERROR) {
            throw new InvalidArgumentException('Erro ao executar regex');
        }

        return $result === 1;
    }
}
