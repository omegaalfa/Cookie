# 🍪 Omega Alfa Cookie

[![Latest Version on Packagist](https://img.shields.io/packagist/v/omegaalfa/cookie.svg?style=flat-square)](https://packagist.org/packages/omegaalfa/cookie)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version Require](https://img.shields.io/packagist/php/omegaalfa/cookie?style=flat-square)](https://packagist.org/packages/omegaalfa/cookie)
[![Tests](https://img.shields.io/github/actions/workflow/status/omegaalfa/cookie/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/omegaalfa/cookie/actions)
[![Code Coverage](https://img.shields.io/badge/coverage-98%25-brightgreen.svg?style=flat-square)](https://github.com/omegaalfa/cookie)
[![Total Downloads](https://img.shields.io/packagist/dt/omegaalfa/cookie.svg?style=flat-square)](https://packagist.org/packages/omegaalfa/cookie)

Uma biblioteca PHP moderna, segura e elegante para gerenciamento de cookies. Projetada com interface estática fluida, focada em segurança e nas melhores práticas de desenvolvimento.

---

## 📋 Índice

- [Características](#-características)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Início Rápido](#-início-rápido)
- [Documentação da API](#-documentação-da-api)
  - [set()](#setname-value-expiration-path-domain-secure-httponly-samesite)
  - [get()](#getname-defaultvalue)
  - [exists()](#existsname)
  - [delete()](#deletename-path-domain-secure)
  - [getAllCookies()](#getallcookies)
  - [clearAllCookies()](#clearallcookies)
  - [setCookieOptions()](#setcookieoptionsexpiration-path-domain-secure-httponly-samesite)
  - [checkCookieConsent()](#checkcookieconsent)
  - [getCookieValueByRegex()](#getcookievaluebyregexregex)
  - [deleteCookiesByRegex()](#deletecookiesbyregexregex)
- [Guia de Segurança](#-guia-de-segurança)
- [Consentimento de Cookies (LGPD/GDPR)](#-consentimento-de-cookies-lgpdgdpr)
- [Testes](#-testes)
- [Contribuindo](#-contribuindo)
- [Licença](#-licença)

---

## ✨ Características

| Recurso | Descrição |
|---------|-----------|
| 🔒 **Seguro por Padrão** | Cookies com `HttpOnly`, `Secure` e `SameSite='Lax'` habilitados automaticamente |
| 🛡️ **Proteção contra ReDoS** | Validação de expressões regulares para prevenir ataques de negação de serviço |
| ✍️ **Consentimento HMAC** | Verificação criptográfica de consentimento de cookies (LGPD/GDPR) |
| 🎯 **Interface Fluida** | API estática simples e intuitiva |
| 📦 **Zero Dependências** | Nenhuma dependência externa em produção |
| ✅ **98% Code Coverage** | Amplamente testado com PHPUnit e Infection |

---

## 📌 Requisitos

- PHP 8.0 ou superior
- Extensão `session` (para consentimento via sessão)

---

## 📦 Instalação

```bash
composer require omegaalfa/cookie
```

---

## 🚀 Início Rápido

```php
<?php

use omegaalfa\Cookie\Cookie;

// Definir um cookie (seguro por padrão)
Cookie::set('usuario', 'joao_silva');

// Definir com expiração de 7 dias
Cookie::set('preferencias', 'tema=dark', time() + (7 * 24 * 60 * 60));

// Obter valor do cookie
$usuario = Cookie::get('usuario');

// Obter com valor padrão
$tema = Cookie::get('tema', 'light');

// Verificar se existe
if (Cookie::exists('usuario')) {
    echo "Usuário logado!";
}

// Deletar cookie
Cookie::delete('usuario');
```

---

## 📖 Documentação da API

### `set($name, $value, $expiration, $path, $domain, $secure, $httpOnly, $sameSite)`

Define um cookie com configurações de segurança.

#### Parâmetros

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `$name` | `string` | *obrigatório* | Nome do cookie |
| `$value` | `string` | *obrigatório* | Valor do cookie |
| `$expiration` | `int\|null` | `0` | Timestamp Unix de expiração. `0` = sessão do navegador |
| `$path` | `string\|null` | `"/"` | Caminho onde o cookie é válido |
| `$domain` | `string\|null` | `""` | Domínio onde o cookie é válido |
| `$secure` | `bool\|null` | `true` | Enviar apenas via HTTPS |
| `$httpOnly` | `bool\|null` | `true` | Inacessível via JavaScript |
| `$sameSite` | `string\|null` | `"Lax"` | Política SameSite (`Strict`, `Lax`, `None`) |

#### Retorno

`bool` - `true` se o cookie foi definido com sucesso.

#### Exemplos

```php
// Cookie de sessão (expira ao fechar navegador)
Cookie::set('session_token', 'abc123');

// Cookie com expiração de 1 hora
Cookie::set('carrinho', json_encode($itens), time() + 3600);

// Cookie com expiração de 30 dias
Cookie::set('lembrar_me', $token, time() + (30 * 24 * 60 * 60));

// Cookie com todas as opções personalizadas
Cookie::set(
    name: 'analytics_id',
    value: 'UA-12345',
    expiration: time() + (365 * 24 * 60 * 60), // 1 ano
    path: '/',
    domain: '.meusite.com.br',
    secure: true,
    httpOnly: false,  // Acessível via JavaScript (analytics)
    sameSite: 'Strict'
);

// Cookie para subdomínio específico
Cookie::set('config', 'value', time() + 3600, '/admin', 'admin.meusite.com');
```

---

### `get($name, $defaultValue)`

Obtém o valor de um cookie.

#### Parâmetros

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `$name` | `string` | *obrigatório* | Nome do cookie |
| `$defaultValue` | `mixed` | `null` | Valor retornado se o cookie não existir |

#### Retorno

`mixed` - Valor do cookie ou o valor padrão.

#### Exemplos

```php
// Obter cookie simples
$token = Cookie::get('auth_token');

// Com valor padrão
$idioma = Cookie::get('idioma', 'pt-BR');
$tema = Cookie::get('tema', 'light');
$itens_por_pagina = Cookie::get('itens_por_pagina', 10);

// Verificando null
$valor = Cookie::get('opcional');
if ($valor === null) {
    // Cookie não existe
}
```

> ⚠️ **Segurança XSS**: Sempre escape a saída ao exibir em HTML:
> ```php
> echo htmlspecialchars(Cookie::get('nome', ''), ENT_QUOTES, 'UTF-8');
> ```

---

### `exists($name)`

Verifica se um cookie existe.

#### Parâmetros

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `$name` | `string` | Nome do cookie |

#### Retorno

`bool` - `true` se o cookie existe.

#### Exemplos

```php
// Verificação simples
if (Cookie::exists('usuario_id')) {
    $usuario = carregarUsuario(Cookie::get('usuario_id'));
}

// Verificar múltiplos cookies
$cookiesNecessarios = ['session', 'csrf_token', 'user_id'];
$todosPresentes = true;

foreach ($cookiesNecessarios as $cookie) {
    if (!Cookie::exists($cookie)) {
        $todosPresentes = false;
        break;
    }
}

// Padrão de autenticação
if (!Cookie::exists('auth_token')) {
    header('Location: /login');
    exit;
}
```

---

### `delete($name, $path, $domain, $secure)`

Remove um cookie.

#### Parâmetros

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `$name` | `string` | *obrigatório* | Nome do cookie |
| `$path` | `string` | `""` | Caminho do cookie (deve corresponder ao original) |
| `$domain` | `string` | `""` | Domínio do cookie (deve corresponder ao original) |
| `$secure` | `bool` | `false` | Flag secure (deve corresponder ao original) |

#### Retorno

`bool` - `true` se o cookie foi deletado com sucesso.

#### Exemplos

```php
// Deletar cookie simples
Cookie::delete('preferencias');

// Deletar cookie com path específico
Cookie::delete('admin_session', '/admin');

// Deletar cookie com domínio
Cookie::delete('global_session', '/', '.meusite.com.br');

// Deletar múltiplos cookies
$cookiesParaDeletar = ['cart', 'wishlist', 'recently_viewed'];
foreach ($cookiesParaDeletar as $cookie) {
    Cookie::delete($cookie);
}

// Logout completo
function logout(): void {
    Cookie::delete('auth_token');
    Cookie::delete('refresh_token');
    Cookie::delete('user_preferences');
    session_destroy();
}
```

> 💡 **Importante**: Para deletar um cookie com sucesso, os parâmetros `path` e `domain` devem corresponder aos valores usados quando o cookie foi criado.

---

### `getAllCookies()`

Retorna todos os cookies disponíveis.

#### Retorno

`array` - Array associativo com todos os cookies (`nome => valor`).

#### Exemplos

```php
// Listar todos os cookies
$cookies = Cookie::getAllCookies();

foreach ($cookies as $nome => $valor) {
    echo "{$nome}: {$valor}\n";
}

// Contar cookies
$total = count(Cookie::getAllCookies());
echo "Total de cookies: {$total}";

// Verificar se há cookies
if (empty(Cookie::getAllCookies())) {
    echo "Nenhum cookie definido";
}

// Debug (apenas em desenvolvimento!)
if ($_ENV['APP_DEBUG'] ?? false) {
    print_r(Cookie::getAllCookies());
}
```

---

### `clearAllCookies()`

Remove todos os cookies do domínio atual.

#### Retorno

`void`

#### Exemplos

```php
// Limpar todos os cookies
Cookie::clearAllCookies();

// Uso em logout completo
function logoutCompleto(): void {
    // Limpar sessão
    session_unset();
    session_destroy();
    
    // Limpar todos os cookies
    Cookie::clearAllCookies();
    
    // Redirecionar
    header('Location: /');
    exit;
}

// Resetar preferências do usuário
function resetarPreferencias(): void {
    Cookie::clearAllCookies();
    
    // Redefinir cookies padrão
    Cookie::set('idioma', 'pt-BR');
    Cookie::set('tema', 'light');
}
```

> ⚠️ **Atenção**: Este método remove TODOS os cookies, incluindo tokens de autenticação. Use com cautela.

---

### `setCookieOptions($expiration, $path, $domain, $secure, $httpOnly, $sameSite)`

Gera um array de opções compatível com `setcookie()` do PHP 7.3+.

#### Parâmetros

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `$expiration` | `int\|null` | - | Timestamp de expiração |
| `$path` | `string\|null` | - | Caminho |
| `$domain` | `string\|null` | - | Domínio |
| `$secure` | `bool\|null` | `false` | Flag Secure |
| `$httpOnly` | `bool\|null` | `false` | Flag HttpOnly |
| `$sameSite` | `string\|null` | `null` | Política SameSite |

#### Retorno

`array` - Array de opções filtrado (valores `null` são removidos).

#### Exemplos

```php
// Gerar opções para uso manual
$options = Cookie::setCookieOptions(
    expiration: time() + 3600,
    path: '/',
    domain: null,
    secure: true,
    httpOnly: true,
    sameSite: 'Strict'
);

// Resultado:
// [
//     'expires' => 1735123456,
//     'path' => '/',
//     'secure' => true,
//     'httponly' => true,
//     'samesite' => 'Strict'
// ]

// Usar com setcookie nativo
setcookie('meu_cookie', 'valor', $options);

// Opções mínimas
$minimalOptions = Cookie::setCookieOptions(
    expiration: null,
    path: '/api',
    domain: null,
    secure: null,
    httpOnly: null,
    sameSite: null
);
// Resultado: ['path' => '/api']
```

---

### `checkCookieConsent()`

Verifica se o usuário deu consentimento para armazenar cookies (LGPD/GDPR).

#### Retorno

`bool` - `true` se o consentimento foi dado e é válido.

#### Métodos de Verificação

1. **Via Sessão**: Verifica `$_SESSION['cookie_consent'] === true`
2. **Via Cookie Assinado**: Verifica assinatura HMAC do cookie de consentimento

#### Exemplos

```php
// Verificação simples
if (Cookie::checkCookieConsent()) {
    // Usuário consentiu - pode usar cookies de analytics, marketing, etc.
    Cookie::set('analytics_enabled', 'true');
    carregarGoogleAnalytics();
} else {
    // Mostrar banner de consentimento
    exibirBannerCookies();
}

// Middleware de consentimento
class CookieConsentMiddleware
{
    public function handle($request, $next)
    {
        if (!Cookie::checkCookieConsent()) {
            // Apenas cookies essenciais
            $request->setAttribute('cookies_limitados', true);
        }
        
        return $next($request);
    }
}

// Categorização de cookies
function podeUsarCookie(string $categoria): bool
{
    return match ($categoria) {
        'essencial' => true, // Sempre permitido
        'funcional', 'analytics', 'marketing' => Cookie::checkCookieConsent(),
        default => false,
    };
}
```

---

### `getCookieValueByRegex($regex)`

Obtém valores de cookies que correspondem a um padrão regex.

#### Parâmetros

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `$regex` | `string` | Expressão regular válida (com delimitadores) |

#### Retorno

`array` - Array com os valores dos cookies correspondentes.

#### Exceções

- `InvalidArgumentException` - Regex vazio, muito longo, inválido ou potencialmente inseguro

#### Exemplos

```php
// Buscar todos os cookies de usuário
$userCookies = Cookie::getCookieValueByRegex('/^user_/');

// Buscar cookies de carrinho
$cartCookies = Cookie::getCookieValueByRegex('/^cart_item_\d+$/');

// Buscar cookies de sessão temporária
$tempCookies = Cookie::getCookieValueByRegex('/^temp_/i');

// Buscar por prefixo específico
$analyticsCookies = Cookie::getCookieValueByRegex('/^_ga/');

// Uso prático: carregar itens do carrinho
$itensCarrinho = [];
foreach (Cookie::getCookieValueByRegex('/^cart_/') as $item) {
    $itensCarrinho[] = json_decode($item, true);
}
```

---

### `deleteCookiesByRegex($regex)`

Remove todos os cookies que correspondem a um padrão regex.

#### Parâmetros

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `$regex` | `string` | Expressão regular válida (com delimitadores) |

#### Retorno

`bool` - `true` se todos os cookies foram deletados com sucesso.

#### Exceções

- `InvalidArgumentException` - Regex vazio, muito longo, inválido ou potencialmente inseguro

#### Exemplos

```php
// Limpar todos os cookies temporários
Cookie::deleteCookiesByRegex('/^temp_/');

// Limpar cookies de analytics
Cookie::deleteCookiesByRegex('/^(_ga|_gid|_gat)/');

// Limpar itens do carrinho
Cookie::deleteCookiesByRegex('/^cart_item_/');

// Limpar cookies de um módulo específico
Cookie::deleteCookiesByRegex('/^module_checkout_/');

// Limpar preferências antigas (migração)
Cookie::deleteCookiesByRegex('/^old_pref_/');

// Limpeza seletiva por padrão numérico
Cookie::deleteCookiesByRegex('/^session_\d{4,}$/');
```

---

## 🔐 Guia de Segurança

### Flags de Segurança Padrão

| Flag | Valor Padrão | Proteção |
|------|--------------|----------|
| `Secure` | `true` | Cookie só é enviado via HTTPS |
| `HttpOnly` | `true` | Cookie inacessível via JavaScript (proteção XSS) |
| `SameSite` | `Lax` | Proteção contra CSRF |

### Valores de SameSite

| Valor | Quando Usar |
|-------|-------------|
| `Strict` | Máxima segurança. Cookie não é enviado em requisições cross-site. |
| `Lax` | **(Recomendado)** Cookie enviado apenas em navegações top-level. |
| `None` | Cookie enviado em todas as requisições. **Requer `Secure=true`**. |

### Boas Práticas

```php
// ✅ Cookie de autenticação seguro
Cookie::set(
    'auth_token',
    $token,
    time() + 3600,
    '/',
    '',
    true,      // Secure
    true,      // HttpOnly
    'Strict'   // SameSite mais restritivo
);

// ✅ Cookie de preferência (pode ser acessado via JS)
Cookie::set(
    'ui_theme',
    'dark',
    time() + (365 * 24 * 60 * 60),
    '/',
    '',
    true,
    false,     // HttpOnly = false (JS precisa ler)
    'Lax'
);

// ⚠️ Cookie para integração cross-site (usar com cautela)
Cookie::set(
    'third_party_integration',
    $value,
    time() + 3600,
    '/',
    '',
    true,      // OBRIGATÓRIO quando SameSite=None
    true,
    'None'
);
```

### Proteção XSS na Saída

```php
// ❌ INCORRETO - Vulnerável a XSS
echo "Olá, " . Cookie::get('nome');

// ✅ CORRETO - Sempre escape a saída
echo "Olá, " . htmlspecialchars(Cookie::get('nome', ''), ENT_QUOTES, 'UTF-8');

// ✅ CORRETO - Usando template engine (Twig, Blade, etc.)
// Twig: {{ cookie_nome|e }}
// Blade: {{ $cookieNome }}
```

---

## 🛡️ Consentimento de Cookies (LGPD/GDPR)

A biblioteca oferece um mecanismo robusto de verificação de consentimento usando assinatura HMAC.

### Configuração

**1. Defina a chave secreta** (variável de ambiente):

```bash
# .env
COOKIE_CONSENT_SECRET="sua-chave-secreta-muito-longa-e-aleatoria-min-32-chars"
```

**2. Quando o usuário aceitar os cookies:**

```php
<?php

use omegaalfa\Cookie\Cookie;

function aceitarCookies(): void
{
    $secret = getenv('COOKIE_CONSENT_SECRET');
    $expiracao = time() + (365 * 24 * 60 * 60); // 1 ano
    
    // Cookie de consentimento
    Cookie::set('cookie_consent', 'true', $expiracao);
    
    // Assinatura HMAC (prova criptográfica)
    $assinatura = hash_hmac('sha256', 'cookie_consent:true', $secret);
    Cookie::set('cookie_consent_signature', $assinatura, $expiracao);
}
```

**3. Verificar consentimento:**

```php
if (Cookie::checkCookieConsent()) {
    // Consentimento válido e verificado criptograficamente
    inicializarAnalytics();
    inicializarMarketing();
} else {
    // Apenas cookies essenciais
    exibirBannerConsentimento();
}
```

### Por que usar HMAC?

O HMAC impede que usuários mal-intencionados forjem o consentimento:

```php
// ❌ Atacante tenta forjar (não funciona)
$_COOKIE['cookie_consent'] = 'true';
$_COOKIE['cookie_consent_signature'] = 'valor_inventado';
// checkCookieConsent() retorna FALSE - assinatura inválida

// ✅ Apenas consentimento legítimo funciona
// Assinatura gerada com a chave secreta do servidor
```

---

## 🧪 Testes

### Executar Testes Unitários

```bash
composer test
# ou
vendor/bin/phpunit
```

### Executar com Cobertura

```bash
vendor/bin/phpunit --coverage-text
```

### Executar Testes de Mutação

```bash
vendor/bin/infection
```

### Executar Análise Estática

```bash
vendor/bin/phpstan analyse
```

---

## 🤝 Contribuindo

Contribuições são muito bem-vindas! Por favor:

1. Faça um fork do repositório
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -m 'feat: adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

### Padrões

- Siga o PSR-12 para estilo de código
- Adicione testes para novas funcionalidades
- Mantenha a cobertura de código acima de 90%
- Use [Conventional Commits](https://www.conventionalcommits.org/)

---

## 📄 Licença

Este projeto está licenciado sob a [Licença MIT](LICENSE).

---

<p align="center">
  Desenvolvido com ❤️ por <a href="https://github.com/omegaalfa">Omega Alfa</a>
</p>
