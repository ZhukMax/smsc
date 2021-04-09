<?php

namespace Zhukmax\Smsc;

/**
 * Interface BaseInterface
 * @package Zhukmax\Smsc
 */
interface BaseInterface
{
    public function sendSms(string $phones, string $message, int $translit, $time, $id, $format, $sender, $query, $files);
    public function sendSmsMail(string $phones, $message, $translit, $time, $id, $format, $sender);
    public function getSmsCost($phones, $message, $translit = 0, $format = 0, $sender = false, $query = "");
}
