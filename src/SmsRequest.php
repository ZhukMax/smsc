<?php

namespace Zhukmax\Smsc;

/**
 * Класс с необходимыми для отправки смс данными
 *
 * @category SmsData
 * @package  Zhukmax\Smsc
 * @author   Max Zhuk <mail@zhukmax.com>
 * @license  https://github.com/ZhukMax/smsc/tree/master/src/LICENSE Apache-2.0
 * @link     https://github.com/ZhukMax/smsc/tree/master/src/Interfaces/BaseInterface.php
 */
class SmsRequest
{
    /**
     * Конструктор класса
     *
     * @param string      $phones   Список телефонов через
     *                              запятую или точку с запятой
     * @param string      $message  Текст сообщения
     * @param int         $translit Переводить или нет в транслит (1,2 или 0)
     * @param int         $time     Необходимое время доставки в виде
     *                              строки (DDMMYYhhmm, h1-h2, 0ts, +m)
     * @param int         $id       Идентификатор сообщения. Представляет собой
     *                              32-битное число в диапазоне от 1 до 2147483647
     * @param int|null    $format   Формат сообщения (0 - обычное sms, 1 - flash-sms,
     *                              2 - wap-push, 3 - hlr, 4 - bin,
     *                              5 - bin-hex, 6 - ping-sms, 7 - mms,
     *                              8 - mail, 9 - call)
     * @param string|null $sender   Имя отправителя (Sender ID).
     *                              Для отключения Sender ID по умолчанию необходимо
     *                              в качестве имени передать пустую строку или точку
     * @param string      $query    Строка дополнительных параметров, добавляемая
     *                              в URL-запрос ("valid=01:00&maxsms=3&tz=2")
     * @param array       $files    Массив путей к файлам для
     *                              отправки mms или e-mail сообщений
     */
    public function __construct(
        public string $phones,
        public string $message,
        public int $translit = 0,
        public int $time = 0,
        public int $id = 0,
        public ?int $format = null,
        public ?string $sender = null,
        public string $query = '',
        public array $files = []
    ) {
    }
}
