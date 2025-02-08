<?php

namespace Zhukmax\Smsc;

/**
 * Class Api
 * @package Zhukmax\Smsc
 * @author Max Zhuk <mail@zhukmax.com>
 * @license  https://github.com/ZhukMax/smsc/tree/master/LICENSE Apache-2.0
 */
class Api extends AbstractApi
{
    /**
     * Функция отправки SMS
     * @throws Exception
     */
    public function sendSms(SmsRequest $request): array
    {
        $request->setSender($this->sender)->setCost(3);
        $result = $this->sendCmd('send', (array)$request, $request->files);

        if ($result[1] > 0) {
            $this->log->info(printf(
                "Сообщение отправлено успешно. ID: %d, всего SMS: %d, стоимость: %d, баланс: %d",
                $result[0], $result[1], $result[2], $result[3]
            ));
        } else {
            $this->log->error("Ошибка №" . -$result[1] . ($result[0] ? ", ID: " . $result[0] : ""));
        }

        return $result;
    }

    /**
     * SMTP версия функции отправки SMS.
     */
    public function sendSmsMail(SmsRequest $request): bool
    {
        $to = "send@send.smsc.ru";
        $message = $this->login.":".$this->password.":$id:$time:$translit,$format,$this->sender:$phones:$message";
        $headers = "From: " . $this->from .
            "\nContent-Type: text/plain; charset=" .
            $this->charset . "\n";

        return mail($to, "", $message, $headers);
    }

    /**
     * @throws Exception
     */
    public function getSmsCost(SmsRequest $request): array
    {
        $result = $this->sendCmd("send", (array)$request);

        if ($result[1] > 0) {
            $this->log->info("Стоимость рассылки: $result[0]. Всего SMS: $result[1]");
        } else {
            // @TODO заменить текст ошибки
            $this->log->error("Ошибка № $result[1]");
        }

        return $result;
    }

    /**
     * Функция проверки статуса отправленного SMS или HLR-запроса.
     * @throws Exception
     */
    public function getStatus(array $id, array $phones, int $all = 0): array
    {
        $result = $this->sendCmd("status", [
            "id" => $id, "phone" => $phones, "all" => $all
        ]);

        if (count($id) == 1) {
            $this->logStatus($result);

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

    private function logStatus(array $result): void
    {
        if ($result[1] != "" && $result[1] >= 0) {
            $this->log->info("Статус SMS = $result[0], время изменения статуса - " . date("d.m.Y H:i:s", $result[1]));
        } else {
            $this->log->error("Ошибка № $result[1]");
        }
    }

    public function getBalance(): string
    {
        $result = $this->sendCmd("balance");

        if (isset($result[1])) {
            $errorText = "Ошибка № $result[1]";
            $this->log->error($errorText);
            throw new Exception($errorText);
        }

        $this->log->info("Сумма на счете: $result[0]");
        return $result[0];
    }
}
