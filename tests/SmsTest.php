<?php

namespace Zhukmax\Smsc\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Zhukmax\Smsc\Api;
use Zhukmax\Smsc\SmsRequest;

class SmsTest extends TestCase
{
    public function testSendSmsSuccess()
    {
        // Mock the logger
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        // Mock the SmsService and set expectations
        $smsService = $this->getMockBuilder(Api::class)
            ->onlyMethods(['prepareParams', 'sendCmd'])
            ->setConstructorArgs([$logger])
            ->getMock();
        $smsService->expects($this->once())
            ->method('prepareParams')
            ->willReturn([
                'phones' => '1234567890',
                'mes' => 'Test message',
                'translit' => 0,
                'id' => 123,
                'cost' => 3,
            ]);
        $smsService->expects($this->once())
            ->method('sendCmd')
            ->with('send', [
                'phones' => '1234567890',
                'mes' => 'Test message',
                'translit' => 0,
                'id' => 123,
                'cost' => 3,
            ], [])
            ->willReturn([12345, 1, 3, 100]);

        // Create a new SmsRequest
        $smsRequest = new SmsRequest('1234567890', 'Test message', 0, 0, 123);

        // Call sendSms and verify the result
        $result = $smsService->sendSms($smsRequest);
        $this->assertEquals([12345, 1, 3, 100], $result);
    }
}
