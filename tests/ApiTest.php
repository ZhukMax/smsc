<?php

namespace Zhukmax\Smsc\Tests;

use PHPUnit\Framework\TestCase;
use Zhukmax\Smsc\Api;
use Zhukmax\Smsc\Exception;
use Zhukmax\Smsc\SmsRequest;

class ApiTest extends TestCase
{
    use Helper;

    public function testConstructorNullLoginPass()
    {
        $this->expectException(Exception::class);
        new Api('', '');
    }

    /**
     * @throws \Exception
     */
    public function testConstructorLoginPass()
    {
        $api = new Api($this->login, $this->pass);
        $login = $this->accessProtectedProperty($api, 'login');
        $this->assertEquals($this->login, $login);

        $password = $this->accessProtectedProperty($api, 'password');
        $this->assertEquals($this->pass, $password);
    }

    /**
     * @throws \Exception
     */
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

    /**
     * @throws \Exception
     */
    public function testPrepareParams(): void
    {
        $api = new Api($this->login, $this->pass);

        $phones = '1234567890';
        $message = 'Test message';
        $translit = 1;
        $time = 1234567890;
        $id = 1;
        $format = 3;
        $sender = 'TestSender';
        $query = 'param1=value1&param2=value2';
        $files = ['file1.txt', 'file2.txt'];

        $request = new SmsRequest($phones, $message, $translit, $time, $id, $format, $sender, $query, $files);

        $params = $this->callProtectedMethod($api, 'prepareParams', [$request]);

        $this->assertEquals(3, $params['cost']);
        $this->assertEquals('1234567890', urldecode($params['phones']));
        $this->assertEquals('Test message', urldecode($params['mes']));
        $this->assertEquals(1, $params['translit']);
        $this->assertEquals(1, $params['id']);
        $this->assertEquals('TestSender', urldecode($params['sender']));
        $this->assertEquals('&bin=1', $params['format']);
        $this->assertEquals(urlencode($request->time), $params['time']);
    }

    /**
     * @throws \Exception
     */
    public function testParseQuery()
    {
        $api = new Api($this->login, $this->pass);

        // Test with a simple query string
        $query = 'param1=value1&param2=value2';
        $expectedResult = [
            'param1' => 'value1',
            'param2' => 'value2',
        ];
        $result = $this->callProtectedMethod($api, 'parseQuery', [$query]);
        $this->assertEquals($expectedResult, $result);

        // Test with a query string that contains special characters
        $query = 'param1=hello+world&param2=%23hashtag';
        $expectedResult = [
            'param1' => 'hello world',
            'param2' => '#hashtag',
        ];
        $result = $this->callProtectedMethod($api, 'parseQuery', [$query]);
        $this->assertEquals($expectedResult, $result);

        // Test with an empty query string
        $query = '';
        $expectedResult = [];
        $result = $this->callProtectedMethod($api, 'parseQuery', [$query]);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSendCmdReturnsArray()
    {
        $this->getResult();
    }

    /**
     * @throws \Exception
     */
    public function testSendCmdUsesCorrectDelimiterForStatusCommand()
    {
        $api = new Api($this->login, $this->pass);
        $cmd = 'status';
        $props = ['id' => '123,456'];
        $files = [];

        $result = $this->callProtectedMethod($api, 'sendCmd', [$cmd, $props, $files]);

        $this->assertIsArray($result);
        $this->assertNotContains(",", $result);
    }

    /**
     * @throws \Exception
     */
    public function testSendCmdWithValidUrlReturnsNonEmptyArray()
    {
        $result = $this->getResult();
        $this->assertNotEmpty($result);
    }

    /**
     * @throws \Exception
     */
    public function getResult(): array
    {
        $api = new Api($this->login, $this->pass);
        $cmd = 'somecmd';
        $props = ['param1' => 'value1', 'param2' => 'value2'];
        $files = [];

        $result = $this->callProtectedMethod($api, 'sendCmd', [$cmd, $props, $files]);

        $this->assertIsArray($result);
        return $result;
    }
}
