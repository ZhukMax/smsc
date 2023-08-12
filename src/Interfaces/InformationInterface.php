<?php

namespace Zhukmax\Smsc\Interfaces;

use Exception;

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
     * Функция получения стоимости SMS.
     *
     * @param array       $phones
     * @param string      $message
     * @param int         $translit - переводить или нет в
     *                              транслит (1,2 или 0)
     * @param int         $format
     * @param string|null $sender   имя
     *                              отправителя
     *                              (Sender ID)
     * @param string      $query    - строка
     *                              дополнительных
     *                              параметров,
     *                              добавляемая
     *                              в URL-запрос
     *                              ("list=79999999999:Ваш
     *                              пароль:
     *                              123\n78888888888:Ваш
     *                              пароль: 456")
     *
     * @return mixed
     */
    public function getSmsCost(array $phones, string $message, int $translit, int $format, string $sender, string $query);

    /**
     * Функция проверки статуса отправленного SMS или HLR-запроса.
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
     * Функция получения баланса.
     *
     * @return string
     * @throws Exception
     */
    public function getBalance(): string;
}
