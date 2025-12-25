# 🍪 Omega Alfa Cookie

[![Latest Version on Packagist](https://img.shields.io/packagist/v/omegaalfa/cookie.svg?style=flat-square)](https://packagist.org/packages/omegaalfa/cookie)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version Require](https://img.shields.io/packagist/php/omegaalfa/cookie?style=flat-square)](https://packagist.org/packages/omegaalfa/cookie)
[![Tests](https://img.shields.io/github/actions/workflow/status/omegaalfa/cookie/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/omegaalfa/cookie/actions)
[![Code Coverage](https://img.shields.io/badge/coverage-98%25-brightgreen.svg?style=flat-square)](https://github.com/omegaalfa/cookie)
[![Total Downloads](https://img.shields.io/packagist/dt/omegaalfa/cookie.svg?style=flat-square)](https://packagist.org/packages/omegaalfa/cookie)

Uma biblioteca PHP moderna, segura e elegante para gerenciamento de cookies. Projetada com interface estática fluida, criptografia AES-256-GCM, sistema de queue e focada em segurança e nas melhores práticas de desenvolvimento.

---

## 📋 Índice

- [Características](#-características)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Início Rápido](#-início-rápido)
- [Documentação da API](#-documentação-da-api)
  - [Operações Básicas](#operações-básicas)
  - [Criptografia](#criptografia)
  - [Sistema de Queue](#sistema-de-queue)
  - [Cookie Forever](#cookie-forever)
  - [Consentimento](#consentimento)
  - [Operações com Regex](#operações-com-regex)
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
| 🔐 **Criptografia AES-256-GCM** | Criptografia autenticada para dados sensíveis |
| 📦 **Sistema de Queue** | Enfileire cookies e envie todos de uma vez |
| ⏰ **Forever Cookies** | Cookies persistentes de 400 dias (máximo do navegador) |
| 🛡️ **Proteção contra ReDoS** | Validação de expressões regulares para prevenir ataques |
| ✍️ **Consentimento HMAC** | Verificação criptográfica de consentimento (LGPD/GDPR) |
| 🎯 **Interface Fluida** | API estática simples e intuitiva |
| 📦 **Zero Dependências** | Nenhuma dependência externa em produção |
| ✅ **98% Code Coverage** | Amplamente testado com PHPUnit e Infection |

---

## 📌 Requisitos

- PHP 8.2 ou superior
- Extensão OpenSSL (para criptografia)
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

### Operações Básicas

#### `set($name, $value, $expiration, $path, $domain, $secure, $httpOnly, $sameSite)`

Define um cookie com configurações de segurança.

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

```php
// Cookie de sessão
Cookie::set('session_token', 'abc123');

// Cookie com expiração de 1 hora
Cookie::set('carrinho', json_encode($itens), time() + 3600);

// Cookie com todas as opções personalizadas
Cookie::set(
    name: 'analytics_id',
    value: 'UA-12345',
    expiration: time() + (365 * 24 * 60 * 60),
    path: '/',
    domain: '.meusite.com.br',
    secure: true,
    httpOnly: false,
    sameSite: 'Strict'
);
```

---

#### `get($name, $defaultValue)`

Obtém o valor de um cookie.

```php
// Obter cookie simples
$token = Cookie::get('auth_token');

// Com valor padrão
$idioma = Cookie::get('idioma', 'pt-BR');
```

> ⚠️ **Segurança XSS**: Sempre escape a saída ao exibir em HTML:
> ```php
> echo htmlspecialchars(Cookie::get('nome', ''), ENT_QUOTES, 'UTF-8');
> ```

---

#### `exists($name)`

Verifica se um cookie existe.

```php
if (Cookie::exists('usuario_id')) {
    $usuario = carregarUsuario(Cookie::get('usuario_id'));
}
```

---

#### `delete($name, $path, $domain, $secure)`

Remove um cookie.

```php
// Deletar cookie simples
Cookie::delete('preferencias');

// Deletar cookie com path específico
Cookie::delete('admin_session', '/admin');

// Deletar cookie com domínio
Cookie::delete('global_session', '/', '.meusite.com.br');
```

> 💡 **Importante**: Os parâmetros `path` e `domain` devem corresponder aos valores originais.

---

#### `getAllCookies()`

Retorna todos os cookies disponíveis.

```php
$cookies = Cookie::getAllCookies();
foreach ($cookies as $nome => $valor) {
    echo "{$nome}: {$valor}\n";
}
```

---

#### `clearAllCookies()`

Deleta todos os cookies do domínio atual.

```php
// Logout completo
Cookie::clearAllCookies();
session_destroy();
```

---

### Criptografia

A biblioteca suporta criptografia AES-256-GCM para proteger dados sensíveis em cookies.

#### `configureEncryption($key, $encryptByDefault, $except)`

Configura as opções de criptografia.

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `$key` | `string` | *obrigatório* | Chave de criptografia (mínimo 32 bytes) |
| `$encryptByDefault` | `bool` | `false` | Criptografar todos os cookies automaticamente |
| `$except` | `array` | `[]` | Cookies a excluir da criptografia automática |

```php
// Configuração básica
$key = 'sua-chave-de-32-bytes-ou-mais!!!';
Cookie::configureEncryption($key);

// Criptografar todos os cookies automaticamente
Cookie::configureEncryption($key, encryptByDefault: true);

// Criptografar todos exceto alguns
Cookie::configureEncryption(
    key: $key,
    encryptByDefault: true,
    except: ['session_id', 'csrf_token', 'locale']
);
```

---

#### `setEncrypted($name, $value, ...)`

Define um cookie com valor criptografado explicitamente.

```php
Cookie::configureEncryption($key);

// O valor será criptografado automaticamente
Cookie::setEncrypted('user_data', json_encode([
    'id' => 123,
    'email' => 'usuario@email.com',
    'role' => 'admin'
]));
```

---

#### `getDecrypted($name, $defaultValue)`

Obtém e descriptografa um cookie.

```php
$userData = Cookie::getDecrypted('user_data');
if ($userData !== null) {
    $data = json_decode($userData, true);
    echo "Bem-vindo, {$data['email']}!";
}

// Com valor padrão
$settings = Cookie::getDecrypted('settings', '{}');
```

---

#### `encrypt($value)` / `decrypt($value)`

Métodos de baixo nível para criptografar/descriptografar valores.

```php
Cookie::configureEncryption($key);

// Criptografar manualmente
$encrypted = Cookie::encrypt('dado sensível');

// Descriptografar
$decrypted = Cookie::decrypt($encrypted);
// Retorna null se a descriptografia falhar
```

---

### Sistema de Queue

O sistema de queue permite enfileirar múltiplos cookies e enviá-los todos de uma vez. Isso é útil para:
- Definir múltiplos cookies em uma única operação
- Preparar cookies durante o processamento e enviar no final
- Gerenciar cookies em middlewares

#### `queue($name, $value, ...)`

Adiciona um cookie à fila.

```php
// Enfileirar múltiplos cookies
Cookie::queue('user_id', '123');
Cookie::queue('session_token', 'abc123');
Cookie::queue('preferences', 'dark_mode=true');
```

---

#### `queueEncrypted($name, $value, ...)`

Adiciona um cookie criptografado à fila.

```php
Cookie::configureEncryption($key);

Cookie::queueEncrypted('sensitive_data', json_encode([
    'credit_card_last4' => '4242',
    'payment_method' => 'visa'
]));
```

---

#### `queueForever($name, $value, ...)`

Adiciona um cookie "forever" (400 dias) à fila.

```php
Cookie::queueForever('remember_me', $token);
```

---

#### `queueDelete($name, $path, $domain)`

Enfileira a remoção de um cookie.

```php
Cookie::queueDelete('old_session');
Cookie::queueDelete('legacy_cookie', '/old-path');
```

---

#### `sendQueued()`

Envia todos os cookies da fila.

```php
// Enfileirar cookies durante o processamento
Cookie::queue('analytics', 'page_view');
Cookie::queue('cart_count', '5');
Cookie::queueEncrypted('user_token', $token);

// Enviar todos ao final
Cookie::sendQueued();
```

---

#### Outros métodos de queue

```php
// Verificar se um cookie está na fila
if (Cookie::hasQueued('user_id')) {
    // ...
}

// Obter dados de um cookie enfileirado
$queued = Cookie::getQueued('user_id');
// ['value' => '123', 'options' => [...]]

// Obter todos os cookies enfileirados
$all = Cookie::getAllQueued();

// Remover um cookie da fila
Cookie::unqueue('user_id');

// Limpar a fila sem enviar
Cookie::flushQueue();

// Contar cookies na fila
$count = Cookie::queueCount();
```

---

### Cookie Forever

#### `forever($name, $value, ...)`

Define um cookie com expiração de 400 dias (máximo permitido pelos navegadores).

```php
// Cookie persistente para "lembrar-me"
Cookie::forever('remember_token', $token);

// Equivalente a:
Cookie::set('remember_token', $token, time() + (400 * 24 * 60 * 60));
```

---

### Consentimento

#### `checkCookieConsent()`

Verifica se o usuário deu consentimento para cookies (LGPD/GDPR).

Suporta dois métodos:
1. **Sessão PHP**: `$_SESSION['cookie_consent'] === true`
2. **Cookie com HMAC**: Cookie `cookie_consent` com assinatura válida

```php
if (Cookie::checkCookieConsent()) {
    // Pode definir cookies de analytics/marketing
    Cookie::set('analytics', 'enabled');
}
```

---

### Operações com Regex

#### `getCookieValueByRegex($regex)`

Obtém valores de cookies que correspondem a um padrão regex.

```php
// Buscar todos os cookies de usuário
$valores = Cookie::getCookieValueByRegex('/^user_/');

// Buscar cookies de sessão
$sessoes = Cookie::getCookieValueByRegex('/^sess_[a-f0-9]+$/');
```

---

#### `deleteCookiesByRegex($regex)`

Remove todos os cookies que correspondem a um padrão regex.

```php
// Deletar todos os cookies de tracking
Cookie::deleteCookiesByRegex('/^tracking_/');

// Limpar cookies temporários
Cookie::deleteCookiesByRegex('/^tmp_/');
```

---

## 🔐 Guia de Segurança

### Configurações Padrão Seguras

| Opção | Padrão | Motivo |
|-------|--------|--------|
| `secure` | `true` | Previne transmissão em conexões não-HTTPS |
| `httpOnly` | `true` | Previne acesso via JavaScript (XSS) |
| `sameSite` | `Lax` | Previne CSRF em requisições cross-site |

### Recomendações

1. **Use HTTPS em produção** - Cookies com `secure=true` só funcionam via HTTPS

2. **Escape output HTML** - Sempre sanitize antes de exibir:
   ```php
   echo htmlspecialchars(Cookie::get('name', ''), ENT_QUOTES, 'UTF-8');
   ```

3. **Use criptografia para dados sensíveis**:
   ```php
   Cookie::configureEncryption($key);
   Cookie::setEncrypted('user_data', $sensitiveData);
   ```

4. **Configure chave de criptografia forte**:
   ```php
   // Gere uma chave segura
   $key = bin2hex(random_bytes(32)); // 64 caracteres hex
   ```

5. **Armazene a chave de forma segura**:
   ```php
   // Use variáveis de ambiente
   $key = getenv('COOKIE_ENCRYPTION_KEY');
   Cookie::configureEncryption($key);
   ```

---

## 📋 Consentimento de Cookies (LGPD/GDPR)

### Via Sessão PHP

```php
session_start();

// Quando usuário aceitar
$_SESSION['cookie_consent'] = true;

// Verificar
if (Cookie::checkCookieConsent()) {
    // OK para definir cookies não-essenciais
}
```

### Via Cookie com HMAC

Configure a variável de ambiente:

```bash
export COOKIE_CONSENT_SECRET="sua-chave-secreta-aqui"
```

```php
$secret = getenv('COOKIE_CONSENT_SECRET');

// Quando usuário aceitar
Cookie::set('cookie_consent', 'true');
Cookie::set('cookie_consent_signature', hash_hmac('sha256', 'cookie_consent:true', $secret));

// Verificar
if (Cookie::checkCookieConsent()) {
    // Consentimento verificado criptograficamente
}
```

---

## 🧪 Testes

```bash
# Executar testes
composer test

# Com cobertura
composer test:coverage

# Mutation testing
composer test:mutation
```

---

## 🤝 Contribuindo

1. Fork o repositório
2. Crie sua branch: `git checkout -b feature/nova-funcionalidade`
3. Commit suas mudanças: `git commit -m 'feat: adiciona nova funcionalidade'`
4. Push para a branch: `git push origin feature/nova-funcionalidade`
5. Abra um Pull Request

### Padrão de Commits

Usamos [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` - Nova funcionalidade
- `fix:` - Correção de bug
- `docs:` - Documentação
- `test:` - Testes
- `refactor:` - Refatoração

---

## 📄 Licença

Este projeto está licenciado sob a [Licença MIT](LICENSE).

---

## 📊 Comparação de Funcionalidades

| Recurso | omegaalfa/cookie | illuminate/cookie | spatie/laravel-cookie-consent |
|---------|------------------|-------------------|-------------------------------|
| Zero Dependências | ✅ | ❌ | ❌ |
| Criptografia AES-256-GCM | ✅ | ✅ | ❌ |
| Sistema de Queue | ✅ | ✅ | ❌ |
| Forever Cookies | ✅ | ✅ | ❌ |
| Proteção ReDoS | ✅ | ❌ | ❌ |
| Consentimento LGPD/GDPR | ✅ | ❌ | ✅ |
| Verificação HMAC | ✅ | ❌ | ❌ |
| Secure por Padrão | ✅ | ✅ | N/A |
| Standalone (sem framework) | ✅ | ❌ | ❌ |
| PHP 8.2+ Nativo | ✅ | ✅ | ✅ |

---

<p align="center">
  Feito com ❤️ por <a href="https://github.com/omegaalfa">Omega Alfa</a>
</p>
