<?php

namespace tests\src;


use omegaalfa\Cookie\tests\MockCookieManagerTest;
use PHPUnit\Framework\TestCase;


class CookieTest extends TestCase
{
    protected function setUp(): void
    {
        // Limpa todos os cookies antes de cada teste
        MockCookieManagerTest::clearAllCookies();
    }

    public function testSetAndGetCookie()
    {
        // Define um cookie
        $this->assertTrue(MockCookieManagerTest::set('test_cookie', 'test_value'));

        // Verifica se o cookie foi definido corretamente
        $this->assertEquals('test_value', MockCookieManagerTest::get('test_cookie'));
    }

    public function testDeleteCookie()
    {
        // Define um cookie
        MockCookieManagerTest::set('test_cookie', 'test_value');

        // Deleta o cookie
        $this->assertTrue(MockCookieManagerTest::delete('test_cookie'));

        // Verifica se o cookie foi deletado
        $this->assertNull(MockCookieManagerTest::get('test_cookie'));
    }

    public function testCookieExists()
    {
        // Define um cookie
        MockCookieManagerTest::set('test_cookie', 'test_value');

        // Verifica se o cookie existe
        $this->assertTrue(MockCookieManagerTest::exists('test_cookie'));

        // Deleta o cookie
        MockCookieManagerTest::delete('test_cookie');

        // Verifica se o cookie não existe mais
        $this->assertFalse(MockCookieManagerTest::exists('test_cookie'));
    }

    public function testIsSecure()
    {
        // Define um cookie seguro
        $this->assertTrue(MockCookieManagerTest::set('test_cookie', 'test_value', null, null, null, true));

        // Verifica se o cookie é seguro
        $this->assertTrue(MockCookieManagerTest::isSecure('test_cookie'));
    }

    public function testIsHttpOnly()
    {
        // Define um cookie HTTP-only
        $this->assertTrue(MockCookieManagerTest::set('test_cookie', 'test_value', null, null, null, false, true));

        // Verifica se o cookie é HTTP-only
        $this->assertTrue(MockCookieManagerTest::isHttpOnly('test_cookie'));
    }

    public function testGetExpirationTime()
    {
        // Define um cookie com tempo de expiração
        $expiration = time() + 3600;
        $this->assertTrue(MockCookieManagerTest::set('test_cookie', 'test_value', $expiration));

        // Verifica o tempo de expiração do cookie
        $this->assertEquals($expiration, MockCookieManagerTest::getExpirationTime('test_cookie'));
    }

    public function testGetDomain()
    {
        // Define um cookie com domínio
        $this->assertTrue(MockCookieManagerTest::set('test_cookie', 'test_value', null, null, 'example.com'));

        // Verifica o domínio do cookie
        $this->assertEquals('example.com', MockCookieManagerTest::getDomain('test_cookie'));
    }

    public function testGetPath()
    {
        // Define um cookie com caminho
        $this->assertTrue(MockCookieManagerTest::set('test_cookie', 'test_value', null, '/path'));

        // Verifica o caminho do cookie
        $this->assertEquals('/path', MockCookieManagerTest::getPath('test_cookie'));
    }

    public function testGetAllCookies()
    {
        // Define dois cookies
        MockCookieManagerTest::set('test_cookie1', 'test_value1');
        MockCookieManagerTest::set('test_cookie2', 'test_value2');

        // Verifica se ambos os cookies foram definidos
        $allCookies = MockCookieManagerTest::getAllCookies();
        $this->assertCount(2, $allCookies);

        $this->assertEquals('test_value1', MockCookieManagerTest::get('test_cookie1'));
        $this->assertEquals('test_value2', MockCookieManagerTest::get('test_cookie2'));

        // Limpa todos os cookies
        MockCookieManagerTest::clearAllCookies();

        // Verifica se todos os cookies foram limpos
        $this->assertEmpty((MockCookieManagerTest::getAllCookies()));
    }

    public function testClearAllCookies()
    {
        // Define dois cookies
        MockCookieManagerTest::set('test_cookie1', 'test_value1');
        MockCookieManagerTest::set('test_cookie2', 'test_value2');

        // Limpa todos os cookies
        MockCookieManagerTest::clearAllCookies();

        // Verifica se todos os cookies foram limpos
        $this->assertEmpty(MockCookieManagerTest::getAllCookies());
    }

    public function testCheckCookieConsent()
    {
        // Define um cookie de consentimento
        MockCookieManagerTest::set('cookie_consent', 'true');

        // Verifica se o consentimento de cookie está presente
        $this->assertTrue(MockCookieManagerTest::checkCookieConsent());

        // Limpa o cookie de consentimento
        MockCookieManagerTest::delete('cookie_consent');

        // Verifica se o consentimento de cookie não está mais presente
        $this->assertFalse(MockCookieManagerTest::checkCookieConsent());
    }

    public function testGetCookieValueByRegex()
    {
        // Define dois cookies
        MockCookieManagerTest::set('test_cookie1', 'value1');
        MockCookieManagerTest::set('test_cookie2', 'value2');

        // Obtém valores de cookies que correspondem a uma expressão regular
        $matches = MockCookieManagerTest::getCookieValueByRegex('/^test_cookie/');
        $this->assertCount(2, $matches);
        $this->assertContains('value1', $matches);
        $this->assertContains('value2', $matches);
    }

    public function testDeleteCookiesByRegex()
    {
        // Define dois cookies
        MockCookieManagerTest::set('test_cookie1', 'value1');
        MockCookieManagerTest::set('test_cookie2', 'value2');

        // Deleta cookies que correspondem a uma expressão regular
        $this->assertTrue(MockCookieManagerTest::deleteCookiesByRegex('/^test_cookie/'));

        // Verifica se os cookies foram deletados
        $this->assertEmpty(MockCookieManagerTest::getAllCookies());
    }
}
