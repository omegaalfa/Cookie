# Cookie - Class for cookie operations in PHP for version php 8

This is a simple PHP class that provides an easy-to-use interface for working with cookies in your web applications.

## Descrição

A classe `Cookie` é uma ferramenta utilitária para gerenciar cookies no PHP. Ela fornece uma interface orientada a objetos para definir, obter, deletar e verificar a existência de cookies, além de outras funcionalidades relacionadas a cookies.

## Installation

You can install the `Cookie` class using Composer:

```bash
composer require omegaalfa/cookie:dev-main
```

```php
use omegaalfa\Cookie;

// Set a cookie
Cookie::make('username', 'John Doe')
     ->withExpiration(3600)
     ->withPath('/')
     ->withDomain('.example.com')
     ->withSecure(true)
     ->withHttpOnly(true)
     ->send();

// Get the value of a cookie
$username = Cookie::get('username');

// Delete a cookie
Cookie::delete('username');

## Métodos

### `set`
Define um novo cookie. Aceita vários parâmetros para configurar o cookie, incluindo nome, valor, tempo de expiração, caminho, domínio, se deve ser seguro, se deve ser acessível apenas via HTTP, e o atributo `SameSite`.
```php
Cookie::set('theme', 'red', time() + 3600, '/', 'example.com', true, true, 'Strict');
```
### `get`
Recupera o valor de um cookie especificado pelo nome. Se o cookie não existir, retorna um valor padrão.

```php
Cookie::get('theme', 'default');
```
### `delete`

Deleta um cookie existente. Aceita parâmetros para o caminho, domínio e se deve ser seguro, além do nome do cookie.
```php
Cookie::delete('theme', '/', 'example.com', true);
```
### `exists`

Verifica se um cookie existe.
```php
if (Cookie::exists('theme')) { // O cookie existe }
```
### `isSecure`

Verifica se um cookie é seguro.
```php
if (Cookie::isSecure('theme')) { // O cookie é seguro }
```
### `isHttpOnly`

Verifica se um cookie é HTTP-only.
```php
if (Cookie::isHttpOnly('theme')) { // O cookie é HTTP-only }
```
### `getExpirationTime`

Retorna o tempo de expiração de um cookie como um timestamp Unix.
```php
$expirationTime = Cookie::getExpirationTime('theme');
```
### `getDomain`

Retorna o domínio associado a um cookie.
```php
$domain = Cookie::getDomain('theme');
```
### `getPath`

Retorna o caminho associado a um cookie.
```php
$path = Cookie::getPath('theme');
```
### `getAllCookies`

Retorna um array de todos os cookies definidos para o domínio atual.
```php
$allCookies = Cookie::getAllCookies();
```
### `clearAllCookies`

Deleta todos os cookies definidos para o domínio atual.
```php
Cookie::clearAllCookies();
```
### `checkCookieConsent`

Verifica se o usuário deu consentimento para armazenar cookies.
```php
if (Cookie::checkCookieConsent()) { // O usuário deu consentimento }
```
### `getCookieValueByRegex`

Retorna um array de valores de cookies que correspondem a uma expressão regular dada.
```php
$matches = Cookie::getCookieValueByRegex('/^theme/');
```
### `deleteCookiesByRegex`

Deleta todos os cookies que correspondem a uma expressão regular dada.
```php
Cookie::deleteCookiesByRegex('/^theme/');
```



// Check if a cookie exists
if (Cookie::exists('name')) {
    // Cookie exists
}
```
## Contribuição

Contribuições são bem-vindas. Por favor, abra um pull request ou issue para discutir mudanças ou adições.

## Licença

Este projeto está licenciado sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.
