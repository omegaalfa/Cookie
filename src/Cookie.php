<?php

namespace omegaalfa\Cookie;

/**
 * Class Cookie
 *
 * @package src\classes
 */
class Cookie
{
	/**
	 * Ex. Cookie::set('theme', 'red');
	 * setcookie('SID', '31d4d96e407aad42', time() + 3600, '/~rasmus/', 'example.com', true, true, 'Strict');
	 *
	 * @param  string       $name
	 * @param  string       $value
	 * @param  int|null     $expiration
	 * @param  string       $path
	 * @param  string       $domain
	 * @param  bool         $secure
	 * @param  bool         $httpOnly
	 * @param  string|null  $sameSite
	 *
	 * @return bool
	 */
	public static function set(
		string $name,
		string $value,
		?int $expiration = null,
		string $path = "",
		string $domain = "",
		bool $secure = false,
		bool $httpOnly = false,
		?string $sameSite = null
	): bool {
		return setcookie($name, $value, [
			'expires'  => $expiration,
			'path'     => $path,
			'domain'   => $domain,
			'secure'   => $secure,
			'httponly' => $httpOnly,
			'samesite' => $sameSite,
		]);
	}


	/**
	 * @param  string  $name
	 * @param  null    $defaultValue
	 *
	 * @return mixed
	 */
	public static function get(string $name, $defaultValue = null): mixed
	{
		if(!self::exists($name)) {
			return $defaultValue;
		}

		return $_COOKIE[$name];
	}


	/**
	 * @param  string  $name
	 * @param  string  $path
	 * @param  string  $domain
	 * @param  bool    $secure
	 *
	 * @return bool
	 */
	public static function delete(string $name, string $path = '', string $domain = '', bool $secure = false): bool
	{
		if(array_key_exists($name, $_COOKIE)) {
			if(false === setcookie($name, '', -1, $path, $domain, $secure)) {
				return false;
			}

			unset($_COOKIE[$name]);
		}

		return true;
	}

	/**
	 * @param  string  $name
	 *
	 * @return bool
	 */
	public static function exists(string $name): bool
	{
		return isset($_COOKIE[$name]);
	}

	/**
	 * Checks if a cookie is secure
	 *
	 * @param  string  $cookieName
	 *
	 * @return bool
	 */
	public static function isSecure(string $cookieName): bool
	{
		if(!self::exists($cookieName)) {
			return false;
		}

		return isset($_COOKIE[$cookieName]['secure']) && $_COOKIE[$cookieName]['secure'] === true;
	}

	/**
	 * Checks if a cookie is HTTP-only
	 *
	 * @param  string  $cookieName
	 *
	 * @return bool
	 */
	public static function isHttpOnly(string $cookieName): bool
	{
		if(!self::exists($cookieName)) {
			return false;
		}

		return isset($_COOKIE[$cookieName]['httponly']) && $_COOKIE[$cookieName]['httponly'] === true;
	}

	/**
	 * Returns the expiration time of a cookie as a Unix timestamp
	 *
	 * @param  string  $cookieName
	 *
	 * @return int|null
	 */
	public static function getExpirationTime(string $cookieName): ?int
	{
		if(!self::exists($cookieName)) {
			return null;
		}

		return isset($_COOKIE[$cookieName]['expires']) ? (int)$_COOKIE[$cookieName]['expires'] : null;
	}

	/**
	 * Returns the domain associated with a cookie
	 *
	 * @param  string  $cookieName
	 *
	 * @return mixed|null
	 */
	public static function getDomain(string $cookieName): mixed
	{
		if(!self::exists($cookieName)) {
			return null;
		}

		return $_COOKIE[$cookieName]['domain'] ?? null;
	}

	/**
	 * Returns the path associated with a cookie
	 *
	 * @param  string  $cookieName
	 *
	 * @return string|null
	 */
	public static function getPath(string $cookieName): ?string
	{
		if(!self::exists($cookieName)) {
			return null;
		}

		return $_COOKIE[$cookieName]['path'] ?? null;
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
	 * Deletes all cookies set for the current domain
	 *
	 * @return void
	 */
	public static function clearAllCookies(): void
	{
		foreach(self::getAllCookies() as $name => $value) {
			self::delete($name);
		}
	}

	/**
	 * Checks if the user has given consent to store cookies
	 *
	 * @return bool
	 */
	public static function checkCookieConsent(): bool
	{
		if(isset($_COOKIE['cookie_consent']) && $_COOKIE['cookie_consent'] === 'true') {
			return true;
		}

		if(isset($_SESSION['cookie_consent']) && $_SESSION['cookie_consent'] === true) {
			return true;
		}

		return false;
	}

	/**
	 * Returns an array of cookie values that match a given regular expression
	 *
	 * @param  string  $regex
	 *
	 * @return array
	 */
	public static function getCookieValueByRegex(string $regex): array
	{
		$matches = [];
		foreach($_COOKIE as $name => $value) {
			if(preg_match($regex, $name)) {
				$matches[] = $value;
			}
		}
		return $matches;
	}

	/**
	 * Deletes all cookies that match a given regular expression
	 *
	 * @param  string  $regex
	 *
	 * @return bool
	 */
	public static function deleteCookiesByRegex(string $regex): bool
	{
		$matchingCookies = self::getCookieValueByRegex($regex);
		foreach(array_keys($matchingCookies) as $name) {
			if(!self::delete($name)) {
				return false;
			}
		}
		return true;
	}
}
