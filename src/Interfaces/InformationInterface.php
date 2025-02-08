<?php

namespace Zhukmax\Smsc\Interfaces;

use Exception;
use Zhukmax\Smsc\SmsRequest;

/**
 * Interface InfoInterface
 *
 * @category Interfaces
 * @package  Zhukmax\Smsc
 * @author   Max Zhuk <mail@zhukmax.com>
 * @license  https://github.com/ZhukMax/smsc/tree/master/src/LICENSE Apache-2.0
 * @link     https://github.com/ZhukMax/smsc/tree/master/src/Interfaces/BaseInterface.php
 */
interface InformationInterface
{
    /**
     * Функция получения стоимости SMS
     *
     * @param SmsRequest $request Данные, необходимые для получения стоимости
     *
     * @return array
     */
    public function getSmsCost(SmsRequest $request): array;

    /**
     * Функция проверки статуса отправленного SMS или HLR-запроса
     *
     * @param array $id     Массив ID сообщений
     * @param array $phones Массив номеров телефона
     * @param int   $all    Вернуть все данные отправленного SMS,
     *                      включая текст сообщения (0, 1 или 2)
     *
     * @return array
     */
    public function getStatus(array $id, array $phones, int $all): array;

    /**
     * Функция получения баланса
     *
     * @return string
     * @throws Exception
     */
    public function getBalance(): string;
}
