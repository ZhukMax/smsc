<?php

namespace Zhukmax\Smsc\Tests;

use PHPUnit\Framework\TestCase;
use Zhukmax\Smsc\Logger;

class LoggerTest extends TestCase
{
    private string $logFile;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->logFile = dirname(__DIR__) . '/test.log';
        $this->logger = new Logger($this->logFile);
    }

    protected function tearDown(): void
    {
        unset($this->logger);

        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testInfo()
    {
        $message = 'Info Message';

        $this->logger->error($message);
        $this->assertFileExists($this->logFile);

        $fileData = file_get_contents($this->logFile);
        $this->assertStringContainsString($message, $fileData);
    }

    public function testError()
    {
        $message = 'Error Message';

        $this->logger->error($message);
        $this->assertFileExists($this->logFile);

        $fileData = file_get_contents($this->logFile);
        $this->assertStringContainsString($message, $fileData);
    }
}
