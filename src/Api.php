<?php

namespace Zhukmax\Smsc;

/**
 * Class Api
 * @package Zhukmax\Smsc
 * @author Max Zhuk <mail@zhukmax.com>
 */
class Api extends AbstractApi
{
    /**
     * Функция отправки SMS.
     * @throws \Exception
     */
    public function sendSms(SmsRequest $request): array
    {
        $params = $this->prepareParams($request);
        $result = $this->sendCmd("send", $params, $request->files);

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

    protected function prepareParams(SmsRequest $request): array
    {
        $params = [
            'cost' => 3,
            'phones' => urlencode($request->phones),
            'mes' => urlencode($request->message),
            'translit' => $request->translit,
            'id' => $request->id,
        ];

        $sender = $request->sender ?? $this->sender;

        if (isset($sender)) {
            $params['sender'] = urlencode($sender);
        }

        if ($request->format !== null) {
            $params['format'] = self::format($request->format);
        }

        if ($request->time) {
            $params['time'] = urlencode($request->time);
        }

        if ($request->query) {
            $params = array_merge($params, $this->parseQuery($request->query));
        }

        return $params;
    }

    protected function parseQuery(string $query): array
    {
        $params = [];
        parse_str($query, $params);

        return $params;
    }

    /**
     * SMTP версия функции отправки SMS.
     */
    public function sendSmsMail(string $phones, string $message, int $translit = 0, int $time = 0, $id = 0, int $format = 0): bool
    {
        $to = "send@send.smsc.ru";
        $message = $this->login.":".$this->password.":$id:$time:$translit,$format,$this->sender:$phones:$message";
        $headers = "From: " . $this->from .
            "\nContent-Type: text/plain; charset=" .
            $this->charset . "\n";

        return mail($to, "", $message, $headers);
    }

    /**
     * Функция получения стоимости SMS.
     *
     * @param array $phones
     * @param string $message
     * @param int $translit
     * @param int|null $format
     * @param string $sender
     * @param string $query
     * @return mixed
     * @throws Exception
     */
    public function getSmsCost(array $phones, string $message, int $translit = 0, int $format = null, string $sender = '', string $query = "")
    {
        $result = $this->sendCmd("send", [
            "cost" => 1,
            "phones" => $phones,
            "mes" => urlencode($message),
            "sender" => $sender,
            "translit" => $translit,
            self::format($format),
            $query
        ]);

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
     *
     * @param array $id
     * @param array $phones
     * @param int $all
     * @return array
     * @throws \Exception
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
            $errorText = "Ошибка № $result[1]";
            $this->log->error($errorText);
            throw new Exception($errorText);
        }

        $this->log->info("Сумма на счете: $result[0]");
        return $result[0];
    }
}
