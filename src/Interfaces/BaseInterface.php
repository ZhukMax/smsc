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
    public function sendSmsMail(string $phones, $message, $translit, $time, $id, $format, $sender);
}
