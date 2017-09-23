<?php
namespace Zhukmax\Smsc;

/**
 * Class Api
 * @package Zhukmax\Smsc
 */
class Api extends AbstractApi
{
    public function send_sms($phones, $message, $translit = 0, $time = 0, $id = 0, $format = 0, $sender = false, $query = "", $files = array())
    {
        static $formats = array(1 => "flash=1", "push=1", "hlr=1", "bin=1", "bin=2", "ping=1", "mms=1", "mail=1", "call=1");

        $m = $this->sendCmd("send", "cost=3&phones=".urlencode($phones)."&mes=".urlencode($message).
            "&translit=$translit&id=$id".($format > 0 ? "&".$formats[$format] : "").
            ($sender === false ? "" : "&sender=".urlencode($sender)).
            ($time ? "&time=".urlencode($time) : "").($query ? "&$query" : ""), $files);

        if ($this->debug) {
            if ($m[1] > 0)
                echo "Сообщение отправлено успешно. ID: $m[0], всего SMS: $m[1], стоимость: $m[2], баланс: $m[3].\n";
            else
                echo "Ошибка №", -$m[1], $m[0] ? ", ID: ".$m[0] : "", "\n";
        }

        return $m;
    }

    public function sendSmsMail($phones, $message, $translit = 0, $time = 0, $id = 0, $format = 0, $sender = "")
    {
        return mail("send@send.smsc.ru", "", $this->login.":".$this->password.":$id:$time:$translit,$format,$sender:$phones:$message", "From: ".$this->from."\nContent-Type: text/plain; charset=".$this->charset."\n");
    }

    public function get_sms_cost($phones, $message, $translit = 0, $format = 0, $sender = false, $query = "")
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
        $m = $this->sendCmd("status", "phone=".urlencode($phone)."&id=".urlencode($id)."&all=".(int)$all);

        if (!strpos($id, ",")) {
            if ($this->debug)
                if ($m[1] != "" && $m[1] >= 0)
                    echo "Статус SMS = $m[0]", $m[1] ? ", время изменения статуса - ".date("d.m.Y H:i:s", $m[1]) : "", "\n";
                else
                    echo "Ошибка №", -$m[1], "\n";

            if ($all && count($m) > 9 && (!isset($m[$idx = $all == 1 ? 14 : 17]) || $m[$idx] != "HLR")) // ',' в сообщении
                $m = explode(",", implode(",", $m), $all == 1 ? 9 : 12);
        }
        else {
            if (count($m) == 1 && strpos($m[0], "-") == 2)
                return explode(",", $m[0]);

            foreach ($m as $k => $v)
                $m[$k] = explode(",", $v);
        }

        return $m;
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
