<?php

namespace Zhukmax\Smsc;

class SmsRequest
{
    public function __construct(
        /** Список телефонов через запятую или точку с запятой */
        public string $phones,
        public string $message,
        public int $translit = 0,
        public int $time = 0,
        public int $id = 0,
        public ?int $format = null,
        public ?string $sender = null,
        public string $query = '',
        public array $files = []
    ) {}
}
