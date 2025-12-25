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

    // =========================================================================
    // ENCRYPTION TESTS
    // =========================================================================

    public function testConfigureEncryption(): void
    {
        $key = str_repeat('a', 32);
        MockCookieManager::configureEncryption($key);
        $this->expectNotToPerformAssertions();
    }

    public function testConfigureEncryptionThrowsExceptionForShortKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MockCookieManager::configureEncryption('short');
    }

    public function testEncryptAndDecrypt(): void
    {
        $key = str_repeat('a', 32);
        MockCookieManager::configureEncryption($key);
        
        $original = 'sensitive data';
        $encrypted = MockCookieManager::encrypt($original);
        
        $this->assertNotEquals($original, $encrypted);
        
        $decrypted = MockCookieManager::decrypt($encrypted);
        $this->assertEquals($original, $decrypted);
    }

    public function testSetEncrypted(): void
    {
        $key = str_repeat('a', 32);
        MockCookieManager::configureEncryption($key);
        
        MockCookieManager::setEncrypted('secure_cookie', 'secret value');
        
        $this->assertTrue(MockCookieManager::exists('secure_cookie'));
        $rawValue = MockCookieManager::get('secure_cookie');
        $this->assertNotEquals('secret value', $rawValue);
    }

    public function testGetDecrypted(): void
    {
        $key = str_repeat('a', 32);
        MockCookieManager::configureEncryption($key);
        
        MockCookieManager::setEncrypted('secure_cookie', 'secret value');
        
        $decrypted = MockCookieManager::getDecrypted('secure_cookie');
        $this->assertEquals('secret value', $decrypted);
    }

    public function testGetDecryptedWithDefault(): void
    {
        $key = str_repeat('a', 32);
        MockCookieManager::configureEncryption($key);
        
        $result = MockCookieManager::getDecrypted('nonexistent', 'default');
        $this->assertEquals('default', $result);
    }

    // =========================================================================
    // FOREVER COOKIE TESTS
    // =========================================================================

    public function testForever(): void
    {
        MockCookieManager::forever('remember_me', 'token');
        
        $this->assertTrue(MockCookieManager::exists('remember_me'));
        $this->assertEquals('token', MockCookieManager::get('remember_me'));
    }

    // =========================================================================
    // QUEUE TESTS
    // =========================================================================

    public function testQueue(): void
    {
        MockCookieManager::queue('queued_cookie', 'queued_value');
        
        $this->assertTrue(MockCookieManager::hasQueued('queued_cookie'));
        $this->assertEquals(1, MockCookieManager::queueCount());
    }

    public function testQueueEncrypted(): void
    {
        $key = str_repeat('a', 32);
        MockCookieManager::configureEncryption($key);
        
        MockCookieManager::queueEncrypted('encrypted_queued', 'secret');
        
        $queued = MockCookieManager::getQueued('encrypted_queued');
        $this->assertNotEquals('secret', $queued['value']);
    }

    public function testQueueForever(): void
    {
        MockCookieManager::queueForever('forever_queued', 'persistent');
        
        $queued = MockCookieManager::getQueued('forever_queued');
        $this->assertGreaterThan(time() + 30000000, $queued['options']['expires']);
    }

    public function testQueueDelete(): void
    {
        MockCookieManager::queueDelete('to_delete');
        
        $queued = MockCookieManager::getQueued('to_delete');
        $this->assertEquals('', $queued['value']);
        $this->assertLessThan(time(), $queued['options']['expires']);
    }

    public function testUnqueue(): void
    {
        MockCookieManager::queue('test', 'value');
        $this->assertTrue(MockCookieManager::hasQueued('test'));
        
        MockCookieManager::unqueue('test');
        $this->assertFalse(MockCookieManager::hasQueued('test'));
    }

    public function testGetQueued(): void
    {
        MockCookieManager::queue('test', 'value');
        
        $queued = MockCookieManager::getQueued('test');
        $this->assertEquals('value', $queued['value']);
        $this->assertArrayHasKey('options', $queued);
    }

    public function testGetQueuedReturnsNullForNonExistent(): void
    {
        $this->assertNull(MockCookieManager::getQueued('nonexistent'));
    }

    public function testGetAllQueued(): void
    {
        MockCookieManager::queue('cookie1', 'value1');
        MockCookieManager::queue('cookie2', 'value2');
        
        $all = MockCookieManager::getAllQueued();
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('cookie1', $all);
        $this->assertArrayHasKey('cookie2', $all);
    }

    public function testSendQueued(): void
    {
        MockCookieManager::queue('cookie1', 'value1');
        MockCookieManager::queue('cookie2', 'value2');
        
        MockCookieManager::sendQueued();
        
        $this->assertEquals(0, MockCookieManager::queueCount());
        $this->assertTrue(MockCookieManager::exists('cookie1'));
        $this->assertTrue(MockCookieManager::exists('cookie2'));
    }

    public function testFlushQueue(): void
    {
        MockCookieManager::queue('cookie1', 'value1');
        MockCookieManager::queue('cookie2', 'value2');
        
        MockCookieManager::flushQueue();
        
        $this->assertEquals(0, MockCookieManager::queueCount());
        $this->assertFalse(MockCookieManager::exists('cookie1'));
    }

    public function testQueueCount(): void
    {
        $this->assertEquals(0, MockCookieManager::queueCount());
        
        MockCookieManager::queue('cookie1', 'value1');
        $this->assertEquals(1, MockCookieManager::queueCount());
        
        MockCookieManager::queue('cookie2', 'value2');
        $this->assertEquals(2, MockCookieManager::queueCount());
    }

    public function testAutoEncryptionWithExcept(): void
    {
        $key = str_repeat('a', 32);
        MockCookieManager::configureEncryption($key, true, ['excluded']);
        
        MockCookieManager::set('encrypted_auto', 'value1');
        MockCookieManager::set('excluded', 'value2');
        
        // encrypted_auto should be encrypted
        $this->assertNotEquals('value1', MockCookieManager::get('encrypted_auto'));
        
        // excluded should NOT be encrypted
        $this->assertEquals('value2', MockCookieManager::get('excluded'));
    }
}
