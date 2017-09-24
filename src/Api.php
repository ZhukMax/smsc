<?php
namespace Zhukmax\Smsc;

/**
 * Class Api
 * @package Zhukmax\Smsc
 */
class Api extends AbstractApi
{
    /**
     * @param string $property
     * @return mixed
     */
    public function getProperty($property)
    {
        return $this->$property;
    }

    /**
     * Функция отправки SMS.
     *
     * @param $phones
     * @param $message
     * @param int $translit
     * @param int $time
     * @param int $id
     * @param int $format
     * @param bool $sender
     * @param string $query
     * @param array $files
     * @return mixed
     */
    public function sendSms($phones, $message, $translit = 0, $time = 0, $id = 0, $format = 0, $sender = false, $query = "", $files = array())
    {
        static $formats = array(1 => "flash=1", "push=1", "hlr=1", "bin=1", "bin=2", "ping=1", "mms=1", "mail=1", "call=1");

        $result = $this->sendCmd("send", "cost=3&phones=".urlencode($phones)."&mes=".urlencode($message).
            "&translit=$translit&id=$id".($format > 0 ? "&".$formats[$format] : "").
            ($sender === false ? "" : "&sender=".urlencode($sender)).
            ($time ? "&time=".urlencode($time) : "").($query ? "&$query" : ""), $files);

        if ($this->debug) {
            if ($result[1] > 0) {
                echo "Сообщение отправлено успешно. ID: $result[0], всего SMS: $result[1], стоимость: $result[2], баланс: $result[3].\n";
            } else {
                echo "Ошибка №", -$result[1], $result[0] ? ", ID: ".$result[0] : "", "\n";
            }
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
    public function sendSmsMail($phones, $message, $translit = 0, $time = 0, $id = 0, $format = 0, $sender = "")
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
     */
    public function getSmsCost($phones, $message, $translit = 0, $format = 0, $sender = false, $query = "")
    {
        static $formats = array(1 => "flash=1", "push=1", "hlr=1", "bin=1", "bin=2", "ping=1", "mms=1", "mail=1", "call=1");

        $m = $this->sendCmd("send", "cost=1&phones=".urlencode($phones)."&mes=".urlencode($message).
            ($sender === false ? "" : "&sender=".urlencode($sender)).
            "&translit=$translit".($format > 0 ? "&".$formats[$format] : "").($query ? "&$query" : ""));

        if ($this->debug) {
            if ($m[1] > 0)
                echo "Стоимость рассылки: $m[0]. Всего SMS: $m[1]\n";
            else
                echo "Ошибка №", -$m[1], "\n";
        }

        return $m;
    }

    /**
     * Функция проверки статуса отправленного SMS или HLR-запроса.
     *
     * @param $id
     * @param $phone
     * @param int $all
     * @return mixed
     */
    public function getStatus($id, $phone, $all = 0)
    {
        $result = $this->sendCmd("status", "phone=".urlencode($phone)."&id=".urlencode($id)."&all=".(int)$all);

        if (!strpos($id, ",")) {
            if ($this->debug) {
                if ($result[1] != "" && $result[1] >= 0) {
                    echo "Статус SMS = $result[0]", $result[1] ? ", время изменения статуса - " . date("d.m.Y H:i:s", $result[1]) : "", "\n";
                } else {
                    echo "Ошибка №", -$result[1], "\n";
                }
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
     * @return array|bool
     */
    public function getBalance()
    {
        $result = $this->sendCmd("balance");

        if ($this->debug) {
            if (!isset($result[1])){
                echo "Сумма на счете: ", $result[0], "\n";
            } else{
                echo "Ошибка №", -$result[1], "\n";
            }
        }

        return isset($result[1]) ? false : $result[0];
    }
}
