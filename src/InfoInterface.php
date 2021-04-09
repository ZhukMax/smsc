<?php

namespace Zhukmax\Smsc;

/**
 * Interface InfoInterface
 * @package Zhukmax\Smsc
 */
interface InfoInterface
{
    /**
     * Функция получения стоимости SMS.
     *
     * @param $phones
     * @param $message
     * @param int $translit
     * @param int $format
     * @param bool $sender
     * @param string $query
     * @return mixed
     */
    public function getSmsCost(string $phones, string $message, int $translit, int $format, bool $sender, string $query);

    /**
     * Функция проверки статуса отправленного SMS или HLR-запроса.
     *
     * @param string $id - ID сообщения или список ID через запятую
     * @param string $phone - номер телефона или список номеров через запятую
     * @param int $all - вернуть все данные отправленного SMS, включая текст сообщения (0,1 или 2)
     * @return array
     */
    public function getStatus(string $id, string $phone, int $all): array;

    /**
     * Функция получения баланса.
     * @return string
     */
    public function getBalance(): string;
}
