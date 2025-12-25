<?php

declare(strict_types=1);

namespace omegaalfa\Cookie\tests;

use omegaalfa\Cookie\Cookie;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CookieTest extends TestCase
{
    private array $originalServer;
    private array $originalEnv;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        $this->originalEnv = $_ENV;
        $_COOKIE = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        putenv('COOKIE_CONSENT_SECRET');
        unset($_ENV['COOKIE_CONSENT_SECRET'], $_SERVER['COOKIE_CONSENT_SECRET']);
        
        // Reset encryption and queue state
        $reflection = new ReflectionClass(Cookie::class);
        
        $encryptionKey = $reflection->getProperty('encryptionKey');
        $encryptionKey->setAccessible(true);
        $encryptionKey->setValue(null, '');
        
        $encryptByDefault = $reflection->getProperty('encryptByDefault');
        $encryptByDefault->setAccessible(true);
        $encryptByDefault->setValue(null, false);
        
        $encryptionExcept = $reflection->getProperty('encryptionExcept');
        $encryptionExcept->setAccessible(true);
        $encryptionExcept->setValue(null, []);
        
        $queue = $reflection->getProperty('queue');
        $queue->setAccessible(true);
        $queue->setValue(null, []);
    }

    protected function tearDown(): void
    {
        $_COOKIE = [];
        $_SERVER = $this->originalServer;
        $_ENV = $this->originalEnv;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        putenv('COOKIE_CONSENT_SECRET');
        unset($_ENV['COOKIE_CONSENT_SECRET'], $_SERVER['COOKIE_CONSENT_SECRET']);
    }

    /** @runInSeparateProcess */
    public function testSet(): void
    {
        $this->assertTrue(Cookie::set('test', 'value'));
    }

    public function testGet(): void
    {
        $this->assertEquals('default', Cookie::get('non_existent', 'default'));
        $_COOKIE['test_cookie'] = 'test_value';
        $this->assertEquals('test_value', Cookie::get('test_cookie'));
    }

    public function testExists(): void
    {
        $this->assertFalse(Cookie::exists('test_cookie'));
        $_COOKIE['test_cookie'] = 'test_value';
        $this->assertTrue(Cookie::exists('test_cookie'));
    }

    /** @runInSeparateProcess */
    public function testDelete(): void
    {
        $_COOKIE['test_cookie'] = 'test_value';
        $this->assertTrue(Cookie::delete('test_cookie'));
        $this->assertArrayNotHasKey('test_cookie', $_COOKIE);
        $this->assertTrue(Cookie::delete('non_existent'));
    }

    public function testGetAllCookies(): void
    {
        $this->assertEmpty(Cookie::getAllCookies());
        $_COOKIE = ['test1' => 'val1', 'test2' => 'val2'];
        $this->assertEquals(['test1' => 'val1', 'test2' => 'val2'], Cookie::getAllCookies());
    }

    /** @runInSeparateProcess */
    public function testClearAllCookies(): void
    {
        $_COOKIE = ['test1' => 'val1', 'test2' => 'val2'];
        Cookie::clearAllCookies();
        $this->assertEmpty($_COOKIE);
    }

    public function testSetCookieOptions(): void
    {
        $expiration = time() + 3600;
        $options = Cookie::setCookieOptions($expiration, '/', 'domain.com', true, true, 'Strict');
        $expected = [
            'expires' => $expiration,
            'path' => '/',
            'domain' => 'domain.com',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ];
        $this->assertEquals($expected, $options);
    }

    public function testSetCookieOptionsWithNullValues(): void
    {
        $options = Cookie::setCookieOptions(null, '/path', null, null, null, null);
        $this->assertEquals(['path' => '/path'], $options);
    }

    public function testSetCookieOptionsPreservesFalseValues(): void
    {
        $options = Cookie::setCookieOptions(0, '/path', '', false, false, 'Lax');
        $expected = [
            'expires' => 0,
            'path' => '/path',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax',
        ];
        $this->assertEquals($expected, $options);
    }

    public function testSetCookieOptionsExcludesEmptyDomain(): void
    {
        $options = Cookie::setCookieOptions(null, '/', '', true, true, 'Strict');
        $this->assertArrayNotHasKey('domain', $options);
    }

    public function testCheckCookieConsentWithValidSignature(): void
    {
        $secret = 'a-very-secret-key';
        $_ENV['COOKIE_CONSENT_SECRET'] = $secret;
        $_COOKIE['cookie_consent'] = 'true';
        $signature = hash_hmac('sha256', 'cookie_consent:true', $secret);
        $_COOKIE['cookie_consent_signature'] = $signature;
        $this->assertTrue(Cookie::checkCookieConsent());
    }

    #[DataProvider('provideHasSessionConsentCases')]
    public function testHasSessionConsent(bool $startSession, ?bool $consentValue, bool $expected): void
    {
        if ($startSession) {
            @session_start();
            if ($consentValue !== null) {
                $_SESSION['cookie_consent'] = $consentValue;
            }
        }
        $this->assertSame($expected, Cookie::checkCookieConsent());
    }

    public static function provideHasSessionConsentCases(): array
    {
        return [
            'active session, consent true' => [true, true, true],
            'active session, consent false' => [true, false, false],
            'active session, consent not set' => [true, null, false],
            'inactive session, consent var set' => [false, true, false],
        ];
    }

    #[DataProvider('provideHasValidSignatureCases')]
    public function testHasValidSignature(
        ?string $secret,
        ?string $consentCookie,
        ?string $signatureCookie,
        bool $expected
    ): void {
        if ($secret !== null) {
            $_ENV['COOKIE_CONSENT_SECRET'] = $secret;
        }
        if ($consentCookie !== null) {
            $_COOKIE['cookie_consent'] = $consentCookie;
        }
        if ($signatureCookie !== null) {
            $_COOKIE['cookie_consent_signature'] = $signatureCookie;
        }
        $this->assertSame($expected, Cookie::checkCookieConsent());
    }

    public static function provideHasValidSignatureCases(): array
    {
        $secret = 'secret';
        $validSignature = hash_hmac('sha256', 'cookie_consent:true', $secret);
        return [
            'all valid' => [$secret, 'true', $validSignature, true],
            'no secret' => [null, 'true', $validSignature, false],
            'no consent cookie' => [$secret, null, $validSignature, false],
            'no signature cookie' => [$secret, 'true', null, false],
            'invalid signature' => [$secret, 'true', 'invalid', false],
            'consent not true' => [$secret, 'false', $validSignature, false],
        ];
    }

    #[DataProvider('provideSecretFallbackCases')]
    public function testGetCookieConsentSecretFallback(
        ?string $env,
        ?string $server,
        ?string $getenv,
        ?string $expected
    ): void {
        if ($env !== null) $_ENV['COOKIE_CONSENT_SECRET'] = $env;
        if ($server !== null) $_SERVER['COOKIE_CONSENT_SECRET'] = $server;
        if ($getenv !== null) putenv("COOKIE_CONSENT_SECRET=$getenv");

        $reflection = new ReflectionClass(Cookie::class);
        $method = $reflection->getMethod('getCookieConsentSecret');
        $method->setAccessible(true);
        $this->assertSame($expected, $method->invoke(null));
    }

    public static function provideSecretFallbackCases(): array
    {
        return [
            'prefers env' => ['env-secret', 'server-secret', 'getenv-secret', 'env-secret'],
            'falls back to server' => [null, 'server-secret', 'getenv-secret', 'server-secret'],
            'falls back to getenv' => [null, null, 'getenv-secret', 'getenv-secret'],
            'all null' => [null, null, null, null],
            'empty strings are null' => ['', '', '', null],
        ];
    }

    public function testGetCookieValueByRegexWithValidRegex(): void
    {
        $_COOKIE = ['user_1' => 'Alice', 'user_2' => 'Bob'];
        $matches = Cookie::getCookieValueByRegex('/^user_/');
        $this->assertCount(2, $matches);
    }

    public function testRegexThrowsExceptionIfEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Cookie::getCookieValueByRegex('');
    }

    public function testRegexAtMaxLengthIsAllowed(): void
    {
        $reflection = new ReflectionClass(Cookie::class);
        $maxLength = $reflection->getConstant('SAFE_REGEX_MAX_LENGTH');
        $regex = '/' . str_repeat('a', $maxLength - 2) . '/';
        Cookie::getCookieValueByRegex($regex);
        $this->expectNotToPerformAssertions();
    }

    public function testRegexThrowsExceptionIfTooLong(): void
    {
        $reflection = new ReflectionClass(Cookie::class);
        $maxLength = $reflection->getConstant('SAFE_REGEX_MAX_LENGTH');
        $regex = '/' . str_repeat('a', $maxLength - 1) . '/';
        $this->expectException(InvalidArgumentException::class);
        Cookie::getCookieValueByRegex($regex);
    }

    public function testDeleteCookiesByRegex(): void
    {
        $_COOKIE = ['user_1' => 'Alice', 'session_id' => 'xyz'];
        $this->assertTrue(Cookie::deleteCookiesByRegex('/^user_/'));
        $this->assertArrayNotHasKey('user_1', $_COOKIE);
        $this->assertArrayHasKey('session_id', $_COOKIE);
    }

    public function testDeleteCookiesByRegexRejectsUnsafePattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Cookie::deleteCookiesByRegex('/(?=unsafe)/');
    }

    public function testMatchesRegexThrowsExceptionOnError(): void
    {
        $_COOKIE['dummy'] = 'value';
        $this->expectException(InvalidArgumentException::class);
        Cookie::getCookieValueByRegex('/(unclosed-group/');
    }

    // =========================================================================
    // ENCRYPTION TESTS
    // =========================================================================

    public function testConfigureEncryptionWithValidKey(): void
    {
        $key = str_repeat('a', 32);
        Cookie::configureEncryption($key);
        $this->expectNotToPerformAssertions();
    }

    public function testConfigureEncryptionThrowsExceptionForShortKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Encryption key must be at least 32 bytes');
        Cookie::configureEncryption('short-key');
    }

    public function testEncryptAndDecryptRoundTrip(): void
    {
        $key = str_repeat('a', 32);
        Cookie::configureEncryption($key);
        
        $originalValue = 'sensitive data';
        $encrypted = Cookie::encrypt($originalValue);
        
        // Encrypted value should be different from original
        $this->assertNotEquals($originalValue, $encrypted);
        
        // Should be base64 encoded
        $this->assertNotFalse(base64_decode($encrypted, true));
        
        // Decryption should return original value
        $decrypted = Cookie::decrypt($encrypted);
        $this->assertEquals($originalValue, $decrypted);
    }

    public function testDecryptReturnsNullForInvalidData(): void
    {
        $key = str_repeat('a', 32);
        Cookie::configureEncryption($key);
        
        // Invalid base64
        $this->assertNull(Cookie::decrypt('not-valid-base64!!!'));
        
        // Valid base64 but too short
        $this->assertNull(Cookie::decrypt(base64_encode('short')));
        
        // Valid base64 but wrong format
        $this->assertNull(Cookie::decrypt(base64_encode(str_repeat('x', 50))));
    }

    public function testEncryptThrowsExceptionWithoutKey(): void
    {
        // Key is already empty from setUp, so this should throw
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Encryption key not configured');
        Cookie::encrypt('test');
    }

    public function testDecryptThrowsExceptionWithoutKey(): void
    {
        // Key is already empty from setUp, so this should throw
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Encryption key not configured');
        Cookie::decrypt('test');
    }

    /** @runInSeparateProcess */
    public function testSetEncrypted(): void
    {
        $key = str_repeat('a', 32);
        Cookie::configureEncryption($key);
        
        $this->assertTrue(Cookie::setEncrypted('secure_cookie', 'secret value'));
    }

    public function testGetDecryptedWithMissingCookie(): void
    {
        $key = str_repeat('a', 32);
        Cookie::configureEncryption($key);
        
        $this->assertEquals('default', Cookie::getDecrypted('nonexistent', 'default'));
    }

    public function testGetDecryptedWithInvalidEncryptedValue(): void
    {
        $key = str_repeat('a', 32);
        Cookie::configureEncryption($key);
        
        $_COOKIE['bad_cookie'] = 'not-encrypted-value';
        $this->assertEquals('fallback', Cookie::getDecrypted('bad_cookie', 'fallback'));
    }

    public function testConfigureEncryptionWithExceptArray(): void
    {
        $key = str_repeat('a', 32);
        Cookie::configureEncryption($key, true, ['session_id', 'csrf_token']);
        
        $reflection = new ReflectionClass(Cookie::class);
        $encryptByDefault = $reflection->getProperty('encryptByDefault');
        $encryptByDefault->setAccessible(true);
        $this->assertTrue($encryptByDefault->getValue(null));
        
        $except = $reflection->getProperty('encryptionExcept');
        $except->setAccessible(true);
        $this->assertEquals(['session_id', 'csrf_token'], $except->getValue(null));
    }

    // =========================================================================
    // FOREVER COOKIE TESTS
    // =========================================================================

    /** @runInSeparateProcess */
    public function testForever(): void
    {
        $this->assertTrue(Cookie::forever('remember_me', 'token_value'));
    }

    public function testForeverConstant(): void
    {
        $reflection = new ReflectionClass(Cookie::class);
        $foreverMinutes = $reflection->getConstant('FOREVER_MINUTES');
        
        // 400 days in minutes = 400 * 24 * 60 = 576000
        $this->assertEquals(576000, $foreverMinutes);
    }

    // =========================================================================
    // QUEUE TESTS
    // =========================================================================

    public function testQueueBasicOperations(): void
    {
        Cookie::queue('test_cookie', 'test_value');
        
        $this->assertTrue(Cookie::hasQueued('test_cookie'));
        $this->assertEquals(1, Cookie::queueCount());
        
        $queued = Cookie::getQueued('test_cookie');
        $this->assertIsArray($queued);
        $this->assertEquals('test_value', $queued['value']);
    }

    public function testGetQueuedReturnsNullForNonExistent(): void
    {
        $this->assertNull(Cookie::getQueued('nonexistent_cookie'));
    }

    public function testUnqueue(): void
    {
        Cookie::queue('to_remove', 'value');
        $this->assertTrue(Cookie::hasQueued('to_remove'));
        
        Cookie::unqueue('to_remove');
        $this->assertFalse(Cookie::hasQueued('to_remove'));
    }

    public function testGetAllQueued(): void
    {
        Cookie::flushQueue(); // Clear any existing queue
        
        Cookie::queue('cookie1', 'value1');
        Cookie::queue('cookie2', 'value2');
        
        $all = Cookie::getAllQueued();
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('cookie1', $all);
        $this->assertArrayHasKey('cookie2', $all);
    }

    public function testFlushQueue(): void
    {
        Cookie::queue('test', 'value');
        $this->assertGreaterThan(0, Cookie::queueCount());
        
        Cookie::flushQueue();
        $this->assertEquals(0, Cookie::queueCount());
    }

    public function testQueueEncrypted(): void
    {
        $key = str_repeat('b', 32);
        Cookie::configureEncryption($key);
        
        Cookie::queueEncrypted('encrypted_cookie', 'secret_data');
        
        $queued = Cookie::getQueued('encrypted_cookie');
        $this->assertNotEquals('secret_data', $queued['value']);
        
        // Should be decryptable
        $decrypted = Cookie::decrypt($queued['value']);
        $this->assertEquals('secret_data', $decrypted);
    }

    public function testQueueForever(): void
    {
        Cookie::flushQueue();
        
        Cookie::queueForever('persistent_cookie', 'long_lived_value');
        
        $queued = Cookie::getQueued('persistent_cookie');
        $this->assertIsArray($queued);
        $this->assertEquals('long_lived_value', $queued['value']);
        
        // Check that expiration is set for ~400 days in the future
        $expectedMinExpiration = time() + (400 * 24 * 60 * 60) - 60; // Allow 60 second tolerance
        $this->assertGreaterThanOrEqual($expectedMinExpiration, $queued['options']['expires']);
    }

    public function testQueueDelete(): void
    {
        Cookie::flushQueue();
        
        Cookie::queueDelete('cookie_to_delete');
        
        $this->assertTrue(Cookie::hasQueued('cookie_to_delete'));
        $queued = Cookie::getQueued('cookie_to_delete');
        
        // Delete queue entry should have empty value
        $this->assertEquals('', $queued['value']);
        
        // Expiration should be in the past
        $this->assertLessThan(time(), $queued['options']['expires']);
    }

    /** @runInSeparateProcess */
    public function testSendQueued(): void
    {
        Cookie::flushQueue();
        
        Cookie::queue('cookie1', 'value1');
        Cookie::queue('cookie2', 'value2');
        
        $this->assertEquals(2, Cookie::queueCount());
        
        $result = Cookie::sendQueued();
        $this->assertTrue($result);
        
        // Queue should be cleared after sending
        $this->assertEquals(0, Cookie::queueCount());
    }

    public function testQueueWithOptions(): void
    {
        Cookie::flushQueue();
        
        $expiration = time() + 7200;
        Cookie::queue('custom_cookie', 'custom_value', $expiration, '/app', '.example.com', true, true, 'Strict');
        
        $queued = Cookie::getQueued('custom_cookie');
        $this->assertEquals($expiration, $queued['options']['expires']);
        $this->assertEquals('/app', $queued['options']['path']);
        $this->assertEquals('.example.com', $queued['options']['domain']);
        $this->assertTrue($queued['options']['secure']);
        $this->assertTrue($queued['options']['httponly']);
        $this->assertEquals('Strict', $queued['options']['samesite']);
    }

    public function testQueueOverwritesSameNameCookie(): void
    {
        Cookie::flushQueue();
        
        Cookie::queue('same_name', 'first_value');
        Cookie::queue('same_name', 'second_value');
        
        $this->assertEquals(1, Cookie::queueCount());
        $queued = Cookie::getQueued('same_name');
        $this->assertEquals('second_value', $queued['value']);
    }
}
