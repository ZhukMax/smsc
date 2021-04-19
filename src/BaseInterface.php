<?php

namespace Zhukmax\Smsc;

/**
 * Interface BaseInterface
 * @package Zhukmax\Smsc
 */
interface BaseInterface
{
    public function sendSms(string $phones, string $message, int $translit, $time, $id, int $format, $sender, $query, $files);
    public function sendSmsMail(string $phones, $message, $translit, $time, $id, $format, $sender);
}
