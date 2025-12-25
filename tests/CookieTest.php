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
}
