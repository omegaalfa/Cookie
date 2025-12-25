<?php

declare(strict_types=1);

namespace omegaalfa\Cookie\tests;

use PHPUnit\Framework\TestCase;

class MockCookieManagerTest extends TestCase
{
    protected function setUp(): void
    {
        MockCookieManager::reset();
    }

    protected function tearDown(): void
    {
        MockCookieManager::reset();
    }

    public function testSetAndGet(): void
    {
        $this->assertTrue(MockCookieManager::set('test', 'value'));
        $this->assertEquals('value', MockCookieManager::get('test'));
    }

    public function testGetWithDefaultValue(): void
    {
        $this->assertEquals('default', MockCookieManager::get('non_existent', 'default'));
    }

    public function testExists(): void
    {
        $this->assertFalse(MockCookieManager::exists('test'));
        MockCookieManager::set('test', 'value');
        $this->assertTrue(MockCookieManager::exists('test'));
    }

    public function testDelete(): void
    {
        MockCookieManager::set('test', 'value');
        $this->assertTrue(MockCookieManager::delete('test'));
        $this->assertFalse(MockCookieManager::exists('test'));
    }

    public function testGetAllCookies(): void
    {
        MockCookieManager::set('cookie1', 'value1');
        MockCookieManager::set('cookie2', 'value2');
        
        $cookies = MockCookieManager::getAllCookies();
        $this->assertCount(2, $cookies);
        $this->assertArrayHasKey('cookie1', $cookies);
        $this->assertArrayHasKey('cookie2', $cookies);
    }

    public function testClearAllCookies(): void
    {
        MockCookieManager::set('cookie1', 'value1');
        MockCookieManager::set('cookie2', 'value2');
        
        MockCookieManager::clearAllCookies();
        $this->assertEmpty(MockCookieManager::getAllCookies());
    }

    public function testSetCookieOptions(): void
    {
        $expiration = time() + 3600;
        $options = MockCookieManager::setCookieOptions($expiration, '/', 'example.com', true, true, 'Strict');
        
        $this->assertEquals($expiration, $options['expires']);
        $this->assertEquals('/', $options['path']);
        $this->assertEquals('example.com', $options['domain']);
        $this->assertTrue($options['secure']);
        $this->assertTrue($options['httponly']);
        $this->assertEquals('Strict', $options['samesite']);
    }

    public function testSetCookieOptionsOmitsNullValues(): void
    {
        $options = MockCookieManager::setCookieOptions(null, '/path', null, null, null, null);
        $this->assertEquals(['path' => '/path'], $options);
    }

    public function testCheckCookieConsent(): void
    {
        $this->assertFalse(MockCookieManager::checkCookieConsent());
        
        MockCookieManager::set('cookie_consent', 'true');
        $this->assertTrue(MockCookieManager::checkCookieConsent());
        
        MockCookieManager::set('cookie_consent', 'false');
        $this->assertFalse(MockCookieManager::checkCookieConsent());
    }

    public function testGetCookieValueByRegex(): void
    {
        MockCookieManager::set('user_1', 'Alice');
        MockCookieManager::set('user_2', 'Bob');
        MockCookieManager::set('session_id', 'xyz');
        
        $matches = MockCookieManager::getCookieValueByRegex('/^user_/');
        $this->assertCount(2, $matches);
        $this->assertContains('Alice', $matches);
        $this->assertContains('Bob', $matches);
    }

    public function testDeleteCookiesByRegex(): void
    {
        MockCookieManager::set('user_1', 'Alice');
        MockCookieManager::set('user_2', 'Bob');
        MockCookieManager::set('session_id', 'xyz');
        
        $this->assertTrue(MockCookieManager::deleteCookiesByRegex('/^user_/'));
        
        $this->assertFalse(MockCookieManager::exists('user_1'));
        $this->assertFalse(MockCookieManager::exists('user_2'));
        $this->assertTrue(MockCookieManager::exists('session_id'));
    }

    public function testSetWithAllOptions(): void
    {
        $expiration = time() + 3600;
        MockCookieManager::set(
            'full_cookie',
            'test_value',
            $expiration,
            '/admin',
            'example.com',
            true,
            true,
            'Strict'
        );
        
        $this->assertTrue(MockCookieManager::exists('full_cookie'));
        $this->assertEquals('test_value', MockCookieManager::get('full_cookie'));
    }

    public function testReset(): void
    {
        MockCookieManager::set('test', 'value');
        MockCookieManager::reset();
        $this->assertFalse(MockCookieManager::exists('test'));
    }
}
