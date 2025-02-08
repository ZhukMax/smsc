<?php

namespace Zhukmax\Smsc;

/**
 * Класс с необходимыми для отправки смс данными
 *
 * @category SmsData
 * @package  Zhukmax\Smsc
 * @author   Max Zhuk <mail@zhukmax.com>
 * @license  https://github.com/ZhukMax/smsc/tree/master/LICENSE Apache-2.0
 * @link     https://github.com/ZhukMax/smsc/tree/master/src/Interfaces/BaseInterface.php
 */
class SmsRequest
{
    private static array $formats = [
        "flash=1",
        "push=1",
        "hlr=1",
        "bin=1",
        "bin=2",
        "ping=1",
        "mms=1",
        "mail=1",
        "call=1",
        "viber=1",
        "soc=1"
    ];

    /**
     * @param string      $phones   Список телефонов через
     *                              запятую или точку с запятой
     *
     * @param string      $message  Текст сообщения
     *
     * @param int         $translit Переводить или нет в транслит (1,2 или 0)
     *
     * @param int         $time     Необходимое время доставки в виде
     *                              строки (DDMMYYhhmm, h1-h2, 0ts, +m)
     *
     * @param int         $id       Идентификатор сообщения. Представляет собой
     *                              32-битное число в диапазоне от 1 до 2147483647
     *
     * @param int|null    $format   Формат сообщения (0 - обычное sms, 1 - flash-sms,
     *                              2 - wap-push, 3 - hlr, 4 - bin,
     *                              5 - bin-hex, 6 - ping-sms, 7 - mms,
     *                              8 - mail, 9 - call)
     *
     * @param string|null $sender   Имя отправителя (Sender ID).
     *                              Для отключения Sender ID по умолчанию необходимо
     *                              в качестве имени передать пустую строку или точку
     *
     * @param string      $query    Строка дополнительных параметров, добавляемая
     *                              в URL-запрос ("valid=01:00&maxsms=3&tz=2")
     * @param array       $files    Массив путей к файлам для
     *                              отправки mms или e-mail сообщений
     *
     * @param int         $cost     Amount
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
        public array $files = [],
        public int $cost = 1
    ) {
    }

    public function setCost(int $cost): SmsRequest
    {
        $this->cost = $cost;
        return $this;
    }

    public function setSender(?string $sender): SmsRequest
    {
        $this->sender??= $sender;
        return $this;
    }

    public function prepare(?string $sender): array
    {
        $params = [
            'cost' => 3,
            'phones' => urlencode($this->phones),
            'mes' => urlencode($this->message),
            'translit' => $this->translit,
            'id' => $this->id,
        ];

        $sender = $this->sender ?? $sender;

        if (isset($sender)) {
            $params['sender'] = urlencode($sender);
        }

        if ($this->format !== null) {
            $params['format'] = $this->getFormat();
        }

        if ($this->time) {
            $params['time'] = urlencode($this->time);
        }

        if ($this->query) {
            $params = array_merge($params, self::parseQuery($this->query));
        }

        return $params;
    }

    public function __toArray(): array
    {
        $params = [
            'cost' => $this->cost,
            'phones' => urlencode($this->phones),
            'mes' => urlencode($this->message),
            'translit' => $this->translit,
        ];

        if ($this->id != 0) {
            $params['id'] = $this->id;
        }

        if (isset($this->sender)) {
            $params['sender'] = urlencode($this->sender);
        }

        if ($this->format !== null) {
            $params['format'] = $this->getFormat();
        }

        if ($this->time) {
            $params['time'] = urlencode($this->time);
        }

        if ($this->query) {
            $params = array_merge($params, self::parseQuery($this->query));
        }

        return $params;
    }

    protected function getFormat(): string
    {
        return $this->format ? "&" . self::$formats[$this->format] : "";
    }

    protected static function parseQuery(string $query): array
    {
        $params = [];
        parse_str($query, $params);

        return $params;
    }
}
