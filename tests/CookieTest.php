<?php

namespace tests\src;

use PHPUnit\Framework\TestCase;
use src\classes\Cookie;

class CookieTest extends TestCase
{
    protected function setUp(): void
    {
        // Limpa todos os cookies antes de cada teste
        Cookie::clearAllCookies();
    }

    public function testSetAndGetCookie()
    {
        // Define um cookie
        $this->assertTrue(Cookie::set('test_cookie', 'test_value'));

        // Verifica se o cookie foi definido corretamente
        $this->assertEquals('test_value', Cookie::get('test_cookie'));
    }

    public function testDeleteCookie()
    {
        // Define um cookie
        Cookie::set('test_cookie', 'test_value');

        // Deleta o cookie
        $this->assertTrue(Cookie::delete('test_cookie'));

        // Verifica se o cookie foi deletado
        $this->assertNull(Cookie::get('test_cookie'));
    }

    public function testCookieExists()
    {
        // Define um cookie
        Cookie::set('test_cookie', 'test_value');

        // Verifica se o cookie existe
        $this->assertTrue(Cookie::exists('test_cookie'));

        // Deleta o cookie
        Cookie::delete('test_cookie');

        // Verifica se o cookie não existe mais
        $this->assertFalse(Cookie::exists('test_cookie'));
    }

    public function testIsSecure()
    {
        // Define um cookie seguro
        $this->assertTrue(Cookie::set('test_cookie', 'test_value', null, null, null, true));

        // Verifica se o cookie é seguro
        $this->assertTrue(Cookie::isSecure('test_cookie'));
    }

    public function testIsHttpOnly()
    {
        // Define um cookie HTTP-only
        $this->assertTrue(Cookie::set('test_cookie', 'test_value', null, null, null, false, true));

        // Verifica se o cookie é HTTP-only
        $this->assertTrue(Cookie::isHttpOnly('test_cookie'));
    }

    public function testGetExpirationTime()
    {
        // Define um cookie com tempo de expiração
        $expiration = time() + 3600;
        $this->assertTrue(Cookie::set('test_cookie', 'test_value', $expiration));

        // Verifica o tempo de expiração do cookie
        $this->assertEquals($expiration, Cookie::getExpirationTime('test_cookie'));
    }

    public function testGetDomain()
    {
        // Define um cookie com domínio
        $this->assertTrue(Cookie::set('test_cookie', 'test_value', null, null, 'example.com'));

        // Verifica o domínio do cookie
        $this->assertEquals('example.com', Cookie::getDomain('test_cookie'));
    }

    public function testGetPath()
    {
        // Define um cookie com caminho
        $this->assertTrue(Cookie::set('test_cookie', 'test_value', null, '/path'));

        // Verifica o caminho do cookie
        $this->assertEquals('/path', Cookie::getPath('test_cookie'));
    }

    public function testGetAllCookies()
    {
        // Define dois cookies
        Cookie::set('test_cookie1', 'test_value1');
        Cookie::set('test_cookie2', 'test_value2');

        // Verifica se ambos os cookies foram definidos
        $allCookies = Cookie::getAllCookies();
        $this->assertCount(2, $allCookies);
        $this->assertEquals('test_value1', $allCookies['test_cookie1']);
        $this->assertEquals('test_value2', $allCookies['test_cookie2']);

        // Limpa todos os cookies
        Cookie::clearAllCookies();

        // Verifica se todos os cookies foram limpos
        $this->assertEmpty(Cookie::getAllCookies());
    }

    public function testClearAllCookies()
    {
        // Define dois cookies
        Cookie::set('test_cookie1', 'test_value1');
        Cookie::set('test_cookie2', 'test_value2');

        // Limpa todos os cookies
        Cookie::clearAllCookies();

        // Verifica se todos os cookies foram limpos
        $this->assertEmpty(Cookie::getAllCookies());
    }

    public function testCheckCookieConsent()
    {
        // Define um cookie de consentimento
        Cookie::set('cookie_consent', 'true');

        // Verifica se o consentimento de cookie está presente
        $this->assertTrue(Cookie::checkCookieConsent());

        // Limpa o cookie de consentimento
        Cookie::delete('cookie_consent');

        // Verifica se o consentimento de cookie não está mais presente
        $this->assertFalse(Cookie::checkCookieConsent());
    }

    public function testGetCookieValueByRegex()
    {
        // Define dois cookies
        Cookie::set('test_cookie1', 'value1');
        Cookie::set('test_cookie2', 'value2');

        // Obtém valores de cookies que correspondem a uma expressão regular
        $matches = Cookie::getCookieValueByRegex('/^test_cookie/');
        $this->assertCount(2, $matches);
        $this->assertContains('value1', $matches);
        $this->assertContains('value2', $matches);
    }

    public function testDeleteCookiesByRegex()
    {
        // Define dois cookies
        Cookie::set('test_cookie1', 'value1');
        Cookie::set('test_cookie2', 'value2');

        // Deleta cookies que correspondem a uma expressão regular
        $this->assertTrue(Cookie::deleteCookiesByRegex('/^test_cookie/'));

        // Verifica se os cookies foram deletados
        $this->assertEmpty(Cookie::getAllCookies());
    }
}
