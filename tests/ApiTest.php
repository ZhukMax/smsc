<?php

namespace Zhukmax\Smsc\Tests;

use PHPUnit\Framework\TestCase;
use Zhukmax\Smsc\Api;
use Zhukmax\Smsc\Exception;

class ApiTest extends TestCase
{
    use Helper;

    private string $login = 'test';
    private string $pass = '123';
    private string $from = 'test@domain.com';
    private string $sender = 'Sender';

    public function testConstructorNullLoginPass()
    {
        $this->expectException(Exception::class);
        new Api('', '');
    }

    public function testConstructorLoginPass()
    {
        $api = new Api($this->login, $this->pass);
        $login = $this->accessProtectedProperty($api, 'login');
        $this->assertEquals($this->login, $login);

        $password = $this->accessProtectedProperty($api, 'password');
        $this->assertEquals($this->pass, $password);
    }

    public function testConstructorProps()
    {
        $api = new Api($this->login, $this->pass, [
            'https' => true,
            'from' => $this->from,
            'sender' => $this->sender,
            'post' => true
        ]);

        $protocol = $this->accessProtectedProperty($api, 'protocol');
        $this->assertEquals('https', $protocol);

        $charset = $this->accessProtectedProperty($api, 'charset');
        $this->assertEquals('utf-8', $charset);

        $from = $this->accessProtectedProperty($api, 'from');
        $this->assertEquals($this->from, $from);

        $httpPost = $this->accessProtectedProperty($api, 'httpPost');
        $this->assertTrue($httpPost);

        $sender = $this->accessProtectedProperty($api, 'sender');
        $this->assertEquals($this->sender, $sender);

        $client = $this->accessProtectedProperty($api, 'client');
        $this->assertInstanceOf('\\GuzzleHttp\\Client', $client);

        $logger = $this->accessProtectedProperty($api, 'log');
        $this->assertInstanceOf('\\Zhukmax\\Smsc\\Logger', $logger);

        $url = $this->accessProtectedProperty($api, 'url');
        $this->assertStringContainsString('//smsc.ru/', $url);
    }

    public function testFormat()
    {
        $mock = $this->getMockForAbstractClass('\Zhukmax\Smsc\AbstractApi',
            ['login' => $this->login, 'password' => $this->pass]);
        $format = self::callProtectedMethod($mock, 'format', [1]);

        self::assertEquals("&push=1", $format);
    }

    public function testArgString()
    {
        $mock = $this->getMockForAbstractClass('\Zhukmax\Smsc\AbstractApi',
            ['login' => $this->login, 'password' => $this->pass]);
        $string = self::callProtectedMethod($mock, 'argString', [[
            'cost' => 1,
            'sender' => '',
            'test=123',
            'phones' => ['+184334397638', '+1353553565465']
        ]]);

        self::assertEquals("&cost=1&test=123&phones=%2B184334397638%2C%2B1353553565465", $string);
    }
}
