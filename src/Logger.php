<?php

namespace Zhukmax\Smsc;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;

/**
 * Class Logger
 * @package Zhukmax\Smsc
 * @author Max Zhuk <mail@zhukmax.com>
 */
class Logger
{
    private MonoLogger $logger;

    public function __construct(string $logFile)
    {
        $this->logger = new MonoLogger('smsc');
        $this->logger->pushHandler(new StreamHandler($logFile, MonoLogger::INFO));
    }

    public function info(string $message): void
    {
        $this->logger->info($message);
    }

    public function warning(string $message): void
    {
        $this->logger->warning($message);
    }

    public function error(string $message): void
    {
        $this->logger->error($message);
    }
}
