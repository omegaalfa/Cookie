<?php

namespace omegaalfa\Cookie;


interface CookieInterface
{
	/**
	 * Define um cookie.
	 *
	 * @param  string       $name        O nome do cookie.
	 * @param  string       $value       O valor do cookie.
	 * @param  int|null     $expiration  O tempo de expiração do cookie em segundos. 0 significa "quando o navegador for fechado".
	 * @param  string       $path        O caminho no servidor para o qual o cookie é válido.
	 * @param  string       $domain      O domínio para o qual o cookie é válido.
	 * @param  bool         $secure      Indica se o cookie deve ser enviado apenas sobre uma conexão segura.
	 * @param  bool         $httpOnly    Indica se o cookie deve ser acessível apenas através do protocolo HTTP.
	 * @param  string|null  $sameSite    A política SameSite do cookie.
	 *
	 * @return bool Retorna true se o cookie foi definido com sucesso.
	 */
	public static function set(
		string $name,
		string $value,
		?int $expiration = null,
		string $path = "/",
		string $domain = "",
		bool $secure = false,
		bool $httpOnly = false,
		?string $sameSite = null
	): bool;

	/**
	 * Obtém o valor de um cookie.
	 *
	 * @param  string  $name          O nome do cookie.
	 * @param  mixed   $defaultValue  O valor padrão a ser retornado se o cookie não existir.
	 *
	 * @return mixed O valor do cookie se ele existir, ou o valor padrão se não existir.
	 */
	public static function get(string $name, mixed $defaultValue = null): mixed;

	/**
	 * Deleta um cookie.
	 *
	 * @param  string  $name    O nome do cookie.
	 * @param  string  $path    O caminho no servidor para o qual o cookie é válido.
	 * @param  string  $domain  O domínio para o qual o cookie é válido.
	 * @param  bool    $secure  Indica se o cookie deve ser enviado apenas sobre uma conexão segura.
	 *
	 * @return bool Retorna true se o cookie foi deletado com sucesso.
	 */
	public static function delete(string $name, string $path = '', string $domain = '', bool $secure = false): bool;

	/**
	 * Verifica se um cookie existe.
	 *
	 * @param  string  $name  O nome do cookie.
	 *
	 * @return bool Retorna true se o cookie existir.
	 */
	public static function exists(string $name): bool;

	/**
	 * Verifica se um cookie é seguro.
	 *
	 * @param  string  $cookieName  O nome do cookie.
	 *
	 * @return bool Retorna true se o cookie for seguro.
	 */
	public static function isSecure(string $cookieName): bool;

	/**
	 * Verifica se um cookie é HTTP-only.
	 *
	 * @param  string  $cookieName  O nome do cookie.
	 *
	 * @return bool Retorna true se o cookie for HTTP-only.
	 */
	public static function isHttpOnly(string $cookieName): bool;

	/**
	 * Retorna o tempo de expiração de um cookie como um timestamp Unix.
	 *
	 * @param  string  $cookieName  O nome do cookie.
	 *
	 * @return int|null O timestamp Unix da expiração do cookie, ou null se o cookie não existir.
	 */
	public static function getExpirationTime(string $cookieName): ?int;

	/**
	 * Retorna o domínio associado a um cookie.
	 *
	 * @param  string  $cookieName  O nome do cookie.
	 *
	 * @return mixed|null O domínio do cookie, ou null se o cookie não existir.
	 */
	public static function getDomain(string $cookieName): mixed;

	/**
	 * Retorna o caminho associado a um cookie.
	 *
	 * @param  string  $cookieName  O nome do cookie.
	 *
	 * @return string|null O caminho do cookie, ou null se o cookie não existir.
	 */
	public static function getPath(string $cookieName): ?string;

	/**
	 * Retorna um array de todos os cookies definidos para o domínio atual.
	 *
	 * @return array Todos os cookies definidos.
	 */
	public static function getAllCookies(): array;

	/**
	 * Deleta todos os cookies definidos para o domínio atual.
	 *
	 * @return void
	 */
	public static function clearAllCookies(): void;

	/**
	 * Verifica se o usuário deu consentimento para armazenar cookies.
	 *
	 * @return bool Retorna true se o consentimento foi dado.
	 */
	public static function checkCookieConsent(): bool;

	/**
	 * Retorna um array de valores de cookie que correspondem a uma expressão regular dada.
	 *
	 * @param  string  $regex  A expressão regular para corresponder aos nomes dos cookies.
	 *
	 * @return array Os valores dos cookies que correspondem à expressão regular.
	 */
	public static function getCookieValueByRegex(string $regex): array;

	/**
	 * Deleta todos os cookies que correspondem a uma expressão regular dada.
	 *
	 * @param  string  $regex  A expressão regular para corresponder aos nomes dos cookies.
	 *
	 * @return bool Retorna true se todos os cookies correspondentes foram deletados com sucesso.
	 */
	public static function deleteCookiesByRegex(string $regex): bool;

	/**
	 * Seta a opções de configuração do cookie
	 *
	 * @param  int|null     $expiration
	 * @param  string|null  $path
	 * @param  string|null  $domain
	 * @param  bool|null    $secure
	 * @param  bool|null    $httpOnly
	 * @param  string|null  $sameSite
	 *
	 * @return array
	 */
	public static function setCookieOptions(
		int|null $expiration,
		string|null $path,
		string|null $domain,
		bool|null $secure = false,
		bool|null $httpOnly = false,
		null|string $sameSite = null
	): array;
}
