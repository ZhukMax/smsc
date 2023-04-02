<?php

namespace Zhukmax\Smsc\Interfaces;

use Zhukmax\Smsc\SmsRequest;

/**
 * Interface BaseInterface
 * @package Zhukmax\Smsc
 */
interface BaseInterface
{
    public function sendSms(SmsRequest $request): array;
    public function sendSmsMail(string $phones, string $message, int $translit, int $time, $id, int $format);
}
