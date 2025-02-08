<?php

namespace Zhukmax\Smsc;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Zhukmax\Smsc\Interfaces\BaseInterface;
use Zhukmax\Smsc\Interfaces\InformationInterface;

/**
 * Class AbstractApi
 * @package Zhukmax\Smsc
 * @author Max Zhuk <mail@zhukmax.com>
 * @license  https://github.com/ZhukMax/smsc/tree/master/LICENSE Apache-2.0
 */
abstract class AbstractApi implements BaseInterface, InformationInterface
{
    private const string URL_FORMAT = '%s://smsc.ru/sys/%s.php?login=%s&psw=%s&fmt=1&charset=%s';
    protected string $url;
    protected string $protocol;
    protected Client $client;
    protected Logger $log;

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
     * @throws Exception
     */
    public function __construct(
        protected string $login,
        protected string $password,
        protected string $charset = 'UTF-8',
        protected string $from = 'api@smsc.ru',
        protected ?string $sender = null,
        protected bool $isSecure = true,
        protected bool $httpPost = false,
        string $logFile = ''
    ) {
        if (!$this->login || !$this->password) {
            throw new Exception('Login and password is required');
        }

        $this->protocol = $isSecure ? 'https': 'http';
        $this->client = new Client();
        $this->log = new Logger($logFile);
    }

    protected static function format(?int $id = null): string
    {
        return $id ? '&' . self::$formats[$id] : '';
    }

    public function setSender(?string $sender): AbstractApi
    {
        $this->sender = $sender;
        return $this;
    }

    protected static function argString(array $props = []): string
    {
        $args = [];

        foreach ($props as $key => $value) {
            if (is_array($value)) {
                $value = urlencode(implode(',', $value));
            }

            if (!empty($value)) {
                $args[] = is_string($key) ? "$key=$value" : $value;
            }
        }

        return $args ? '&' . implode('&', $args) : '';
    }

    /**
     * Функция вызова запроса.
     * Формирует URL и делает 5 попыток чтения через разные подключения к сервису
     *
     * @throws Exception
     */
    protected function sendCmd(string $cmd, array $props = [], array $files = []): array
    {
        $url = $this->buildUrl($cmd, $props);
        $result = '';

        for ($i = 0; $i < 5 && $result === ''; $i++) {
            $result = $this->readUrl($url, $files, 3 + $i);
            if ($result === '') {
                $url = $this->replaceUrl($url, $i);
            }
        }

        if ($result === '') {
            $message = "Ошибка чтения адреса: $url";
            $this->log->error($message);
            throw new Exception($message);
        }

        $delimiter = str_contains($cmd, 'status') && str_contains($props['id'], ',') ? "\n" : ',';

        return explode($delimiter, $result);
    }

    private function buildUrl(string $cmd, array $props): string
    {
        $url = sprintf(
            self::URL_FORMAT,
            $this->protocol,
            $cmd,
            urlencode($this->login),
            urlencode($this->password),
            $this->charset
        );

        return $url . self::argString($props);
    }

    private function replaceUrl(string $url, int $i): string
    {
        return str_replace('://', "://www$i", $url);
    }

    /**
     * Функция чтения URL
     * @throws Exception
     */
    protected function readUrl(string $url, array $files = [], int $timeout = 5): string
    {
        $post = $this->httpPost || strlen($url) > 2000 || !empty($files);

        try {
            $client = new Client([
                'timeout' => $timeout,
                'verify' => false
            ]);

            $options = [
                RequestOptions::ALLOW_REDIRECTS => false
            ];

            if ($post) {
                $options[RequestOptions::FORM_PARAMS] = self::postParams($url, $files);
            }

            $response = $client->request($post ? 'POST' : 'GET', $url, $options);
            return (string) $response->getBody();
        } catch (GuzzleException $e) {
            $this->log->error("Ошибка чтения адреса: {$e->getMessage()}");
            throw new Exception("Ошибка чтения адреса: {$e->getMessage()}");
        }
    }

    private static function postParams(string $url, array $files): array
    {
        [, $query] = explode("?", $url, 2);
        parse_str($query, $postParams);

        if (!empty($files)) {
            foreach ($files as $i => $path) {
                if (file_exists($path)) {
                    $postParams["file" . $i] = fopen($path, 'r');
                }
            }
        }

        return $postParams;
    }
}
