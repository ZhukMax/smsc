<?php

namespace Zhukmax\Smsc\Interfaces;

use Zhukmax\Smsc\SmsRequest;

/**
 * Interface BaseInterface
 *
 * @category Interfaces
 * @package  Zhukmax\Smsc
 * @author   Max Zhuk <mail@zhukmax.com>
 * @license  https://github.com/ZhukMax/smsc/tree/master/src/LICENSE Apache-2.0
 * @link     https://github.com/ZhukMax/smsc/tree/master/src/Interfaces/BaseInterface.php
 */
interface BaseInterface
{
    /**
     * Функция отправки SMS
     *
     * @param SmsRequest $request Необходимые для отправки смс данные
     *
     * @return array
     */
    public function sendSms(SmsRequest $request): array;

    /**
     * SMTP версия функции отправки SMS
     *
     * @param SmsRequest $request Необходимые для отправки смс данные
     *
     * @return bool
     */
    public function sendSmsMail(SmsRequest $request): bool;
}
