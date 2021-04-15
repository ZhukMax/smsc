<?php

use PHPUnit\Framework\TestCase;
use Zhukmax\Smsc\Logger;

class LoggerTest extends TestCase
{
    /** @var string */
    private $logFile;
    /** @var Logger */
    private $loggerWithFile;
    /** @var Logger */
    private $loggerWithoutFile;

    protected function setUp(): void
    {
        $this->logFile = dirname(__DIR__) . '/test.log';
        $this->loggerWithFile = new Logger($this->logFile);
        $this->loggerWithoutFile = new Logger();
    }

    protected function tearDown(): void
    {
        unset($this->loggerWithFile);
        unset($this->loggerWithoutFile);

        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testInfo()
    {
        $message = 'Info Message';

        $this->loggerWithoutFile->error($message);
        $this->assertFileDoesNotExist($this->logFile);

        $this->loggerWithFile->error($message);
        $this->assertFileExists($this->logFile);

        $fileData = file_get_contents($this->logFile);
        $this->assertStringContainsString($message, $fileData);
    }

    public function testError()
    {
        $message = 'Error Message';

        $this->loggerWithoutFile->error($message);
        $this->assertFileDoesNotExist($this->logFile);

        $this->loggerWithFile->error($message);
        $this->assertFileExists($this->logFile);

        $fileData = file_get_contents($this->logFile);
        $this->assertStringContainsString($message, $fileData);
    }
}
