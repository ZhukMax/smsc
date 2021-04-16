<?php

namespace Zhukmax\Smsc;

/**
 * Class Api
 * @package Zhukmax\Smsc
 */
class Api extends AbstractApi implements BaseInterface, InfoInterface
{
    /**
     * Функция отправки SMS.
     *
     * @param string $phones Список телефонов через запятую или точку с запятой
     * @param $message
     * @param int $translit
     * @param int $time
     * @param int $id
     * @param int $format
     * @param string $sender
     * @param string $query
     * @param array $files
     * @return mixed
     * @throws \Exception
     */
    public function sendSms(string $phones, string $message, int $translit = 0, $time = 0, $id = 0, $format = 0, $sender = null, $query = "", $files = array())
    {
        static $formats = array(1 => "flash=1", "push=1", "hlr=1", "bin=1", "bin=2", "ping=1", "mms=1", "mail=1", "call=1");
        $sender = isset($sender) ? $sender : $this->sender;

        $result = $this->sendCmd("send", "cost=3&phones=".urlencode($phones)."&mes=".urlencode($message).
            "&translit=$translit&id=$id".($format > 0 ? "&".$formats[$format] : "").
            (!isset($sender) ? "" : "&sender=".urlencode($sender)).
            ($time ? "&time=".urlencode($time) : "").($query ? "&$query" : ""), $files);

        if ($result[1] > 0) {
            $this->log->info("Сообщение отправлено успешно. ID: $result[0], всего SMS: $result[1], стоимость: $result[2], баланс: $result[3]");
        } else {
            $this->log->error("Ошибка №". -$result[1]. $result[0] ? ", ID: ".$result[0] : "");
        }

        return $result;
    }

    /**
     * SMTP версия функции отправки SMS.
     *
     * @param $phones
     * @param $message
     * @param int $translit
     * @param int $time
     * @param int $id
     * @param int $format
     * @param string $sender
     * @return mixed
     */
    public function sendSmsMail(string $phones, $message, $translit = 0, $time = 0, $id = 0, $format = 0, $sender = "")
    {
        $to = "send@send.smsc.ru";
        $message = $this->login.":".$this->password.":$id:$time:$translit,$format,$sender:$phones:$message";
        $headers = "From: " . $this->from .
            "\nContent-Type: text/plain; charset=" .
            $this->charset . "\n";

        return mail($to, "", $message, $headers);
    }

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
     * @throws \Exception
     */
    public function getSmsCost($phones, $message, $translit = 0, $format = 0, $sender = false, $query = "")
    {
        static $formats = array(1 => "flash=1", "push=1", "hlr=1", "bin=1", "bin=2", "ping=1", "mms=1", "mail=1", "call=1");

        $m = $this->sendCmd("send", "cost=1&phones=".urlencode($phones)."&mes=".urlencode($message).
            ($sender === false ? "" : "&sender=".urlencode($sender)).
            "&translit=$translit".($format > 0 ? "&".$formats[$format] : "").($query ? "&$query" : ""));

        if ($m[1] > 0) {
            $this->log->info("Стоимость рассылки: $m[0]. Всего SMS: $m[1]");
        } else {
            // @TODO заменить текст ошибки
            $this->log->error("Ошибка № $m[1]");
        }

        return $m;
    }

    /**
     * Функция проверки статуса отправленного SMS или HLR-запроса.
     *
     * @param string $id ID cообщения или список ID через запятую
     * @param $phone
     * @param int $all
     * @return array
     * @throws \Exception
     */
    public function getStatus(string $id, string $phone, int $all = 0): array
    {
        $result = $this->sendCmd("status", "phone=".urlencode($phone)."&id=".urlencode($id)."&all=$all");

        if (!strpos($id, ",")) {
            if ($result[1] != "" && $result[1] >= 0) {
                $this->log->info("Статус SMS = $result[0], время изменения статуса - " . date("d.m.Y H:i:s", $result[1]));
            } else {
                $this->log->error("Ошибка № $result[1]");
            }

            if ($all && count($result) > 9 && (!isset($result[$idx = $all == 1 ? 14 : 17]) || $result[$idx] != "HLR")) {
                $result = explode(",", implode(",", $result), $all == 1 ? 9 : 12);
            }
        } else {
            if (count($result) == 1 && strpos($result[0], "-") == 2) {
                return explode(",", $result[0]);
            }

            foreach ($result as $k => $v) {
                $result[$k] = explode(",", $v);
            }
        }

        return $result;
    }

    /**
     * Функция получения баланса.
     *
     * @return string
     * @throws \Exception
     */
    public function getBalance(): string
    {
        $result = $this->sendCmd("balance");

        if (isset($result[1])) {
            $this->log->error("Ошибка № $result[1]");
            throw new Exception("Ошибка №" . $result[1]);
        }

        $this->log->info("Сумма на счете: $result[0]");
        return $result[0];
    }
}
