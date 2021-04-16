<?php

namespace Zhukmax\Smsc\Tests;

use PHPUnit\Framework\TestCase;
use Zhukmax\Smsc\Api;
use Zhukmax\Smsc\Exception;

class ApiTest extends TestCase
{
    use Helper;

    private $login = 'test';
    private $pass = '123';
    private $from = 'test@domain.com';
    private $sender = 'Sender';

    public function testConstructorNullLoginPass()
    {
        $this->expectException(Exception::class);
        new Api('', '');
    }

    public function testConstructorLoginPass()
    {
        $api = new Api($this->login, $this->pass);
        $login = $this->accessProtected($api, 'login');
        $this->assertEquals($this->login, $login);

        $password = $this->accessProtected($api, 'password');
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

        $protocol = $this->accessProtected($api, 'protocol');
        $this->assertEquals('https', $protocol);

        $charset = $this->accessProtected($api, 'charset');
        $this->assertEquals('utf-8', $charset);

        $from = $this->accessProtected($api, 'from');
        $this->assertEquals($this->from, $from);

        $httpPost = $this->accessProtected($api, 'httpPost');
        $this->assertTrue($httpPost);

        $sender = $this->accessProtected($api, 'sender');
        $this->assertEquals($this->sender, $sender);

        $client = $this->accessProtected($api, 'client');
        $this->assertInstanceOf('\\GuzzleHttp\\Client', $client);

        $logger = $this->accessProtected($api, 'log');
        $this->assertInstanceOf('\\Zhukmax\\Smsc\\Logger', $logger);

        $url = $this->accessProtected($api, 'url');
        $this->assertStringContainsString('//smsc.ru/', $url);
    }
}
