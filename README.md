# Smsc API
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](license.md)
[![Total Downloads][ico-downloads]][link-downloads]

Компонент для интеграции сервиса SMSC.RU API (smsc.ru) на сайт. Основано на версии 3.6 официального кода.

## Установка
С помощью композера:
```
$ composer require zhukmax/smsc
```

## Использование
Для того, что бы правильно отлавливать Исключения желательно использовать в конструкции try\catch. Не обязательно использовать выход `exit()`, можно эхом вывести текст ошибки и продолжить выполнение скрипта.
```php
<?php
try {
    $sms = new \Zhukmax\Smsc\Api(
        'test',
        '123',
        [
            'https' => true,
            'charset' => 'windows-1251',
            'from' => 'api@smsc.ru',
            'post' => true,
            'debug' => true
        ]
    );
} catch (Exception $exception) {
    exit($exception->getMessage());
}
```
Так же можно унаследовать класс `\Zhukmax\Smsc\Api` и добавить собственное поведение до/после выполнения методов компонента или, даже, переназначить некоторые из них.
```php
<?php
class Sms extends \Zhukmax\Smsc\Api
{
    public function balance()
    {
        var_dump($this->sendCmd('balance'));
    }
}
```

## Свойства конструктора
* логин клиента
* пароль или MD5-хеш пароля в нижнем регистре
* Массив с опциями:
    * protocol - использовать HTTPS протокол, любое значение кроме 'https' приравнивается использованию не защищенного протокола HTTP
    * charset - кодировка сообщения: utf-8 (по умолчанию), koi8-r или windows-1251
    * from - e-mail адрес отправителя
    * post - использовать метод POST, булев
    * debug - флаг отладки, булев

## Методы
#### Публичные методы:
**sendSms()** - Функция отправки SMS

* _обязательные параметры:_
    - $phones - список телефонов через запятую или точку с запятой
    - $message - отправляемое сообщение

* _необязательные параметры:_
    - $translit - переводить или нет в транслит (1,2 или 0)
    - $time - необходимое время доставки в виде строки (DDMMYYhhmm, h1-h2, 0ts, +m)
    - $id - идентификатор сообщения. Представляет собой 32-битное число в диапазоне от 1 до 2147483647.
    - $format - формат сообщения (0 - обычное sms, 1 - flash-sms, 2 - wap-push, 3 - hlr, 4 - bin, 5 - bin-hex, 6 - ping-sms, 7 - mms, 8 - mail, 9 - call)
    - $sender - имя отправителя (Sender ID). Для отключения Sender ID по умолчанию необходимо в качестве имени
передать пустую строку или точку.
    - $query - строка дополнительных параметров, добавляемая в URL-запрос ("valid=01:00&maxsms=3&tz=2")
    - $files - массив путей к файлам для отправки mms или e-mail сообщений

возвращает массив (<id>, <количество sms>, <стоимость>, <баланс>) в случае успешной отправки
либо массив (<id>, -<код ошибки>) в случае ошибки
 
**sendSmsMail()** - SMTP версия функции отправки SMS

**getSmsCost()** - Функция получения стоимости SMS

* _обязательные параметры:_
    - $phones - список телефонов через запятую или точку с запятой
    - $message - отправляемое сообщение

* _необязательные параметры:_
    - $translit - переводить или нет в транслит (1,2 или 0)
    - $format - формат сообщения (0 - обычное sms, 1 - flash-sms, 2 - wap-push, 3 - hlr, 4 - bin, 5 - bin-hex, 6 - ping-sms, 7 - mms, 8 - mail, 9 - call)
    - $sender - имя отправителя (Sender ID)
    - $query - строка дополнительных параметров, добавляемая в URL-запрос ("list=79999999999:Ваш пароль: 123\n78888888888:Ваш пароль: 456")

возвращает массив (<стоимость>, <количество sms>) либо массив (0, -<код ошибки>) в случае ошибки

**getStatus()** - Функция проверки статуса отправленного SMS или HLR-запроса

- $id - ID cообщения или список ID через запятую

- $phone - номер телефона или список номеров через запятую

- $all - вернуть все данные отправленного SMS, включая текст сообщения (0,1 или 2)

_возвращает массив (для множественного запроса двумерный массив):_

_для одиночного SMS-сообщения:_
(<статус>, <время изменения>, <код ошибки доставки>)

_для HLR-запроса:_
(<статус>, <время изменения>, <код ошибки sms>, <код IMSI SIM-карты>, <номер сервис-центра>, <код страны регистрации>, <код оператора>,
<название страны регистрации>, <название оператора>, <название роуминговой страны>, <название роумингового оператора>)

при $all = 1 дополнительно возвращаются элементы в конце массива:
(<время отправки>, <номер телефона>, <стоимость>, <sender id>, <название статуса>, <текст сообщения>)

при $all = 2 дополнительно возвращаются элементы <страна>, <оператор> и <регион>

при множественном запросе:
если $all = 0, то для каждого сообщения или HLR-запроса дополнительно возвращается <ID сообщения> и <номер телефона>

если $all = 1 или $all = 2, то в ответ добавляется <ID сообщения>

либо массив (0, -<код ошибки>) в случае ошибки

**getBalance()** - Функция получения баланса

возвращает баланс в виде строки или false в случае ошибки

#### Внутренние методы:
**sendCmd()** - Функция вызова запроса. Формирует URL и делает 5 попыток чтения через разные подключения к сервису

**readUrl()** - Функция чтения URL.

Для работы должно быть доступно:
curl или fsockopen (только http) или включена опция allow_url_fopen для file_get_contents

## Лицензия

The Apache License Version 2.0. Текст лицензии находится в файле [License File](license.md).

[ico-version]: https://img.shields.io/packagist/v/zhukmax/smsc.svg
[ico-license]: https://img.shields.io/badge/license-Apache%202-brightgreen.svg
[ico-downloads]: https://img.shields.io/packagist/dt/zhukmax/smsc.svg

[link-packagist]: https://packagist.org/packages/zhukmax/smsc
[link-downloads]: https://packagist.org/packages/zhukmax/smsc
