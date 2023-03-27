<?php

namespace Zhukmax\Smsc;

/**
 * Interface BaseInterface
 * @package Zhukmax\Smsc
 */
interface BaseInterface
{
    public function sendSms(SmsRequest $request): array;
    public function sendSmsMail(string $phones, $message, $translit, $time, $id, $format, $sender);
}
