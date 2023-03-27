<?php

namespace Zhukmax\Smsc\Tests;

use PHPUnit\Framework\TestCase;
use Zhukmax\Smsc\Api;
use Zhukmax\Smsc\Exception;

class InfoInterfaceTest extends TestCase
{
    use Helper;

    public function testGetBalanceSuccess()
    {
        $balance = 500;

        $mock = $this->getMockBuilder(Api::class)
            ->setConstructorArgs(['login' => $this->login, 'password' => $this->pass])
            ->setMethods(['sendCmd'])->getMock();
        $mock->method('sendCmd')->willReturn([$balance]);

        $this->assertSame((string)$balance, $mock->getBalance());
    }

    public function testGetBalanceFalse()
    {
        $mock = $this->getMockBuilder(Api::class)
            ->setConstructorArgs(['login' => $this->login, 'password' => $this->pass])
            ->setMethods(['sendCmd'])->getMock();

        $mock->method('sendCmd')->willReturn([0, 500]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Ошибка № 500");
        $mock->getBalance();
    }
}
