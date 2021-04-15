<?php

namespace Zhukmax\Smsc;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;

/**
 * Class Logger
 * @package Zhukmax\Smsc
 */
class Logger
{
    /** @var MonoLogger */
    private $logger;

    /**
     * Logger constructor.
     * @param string $logFile
     */
    public function __construct(string $logFile = '')
    {
        if ($logFile) {
            $this->logger = new MonoLogger('smsc');
            $this->logger->pushHandler(new StreamHandler($logFile, MonoLogger::INFO));
        }
    }

    /**
     * @param string $message
     */
    public function info(string $message): void
    {
        if ($this->logger) {
            $this->logger->info($message);
        }
    }

    /**
     * @param string $message
     */
    public function warning(string $message): void
    {
        if ($this->logger) {
            $this->logger->warning($message);
        }
    }

    /**
     * @param string $message
     */
    public function error(string $message): void
    {
        if ($this->logger) {
            $this->logger->error($message);
        }
    }
}
