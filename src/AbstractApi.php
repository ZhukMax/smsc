<?php

namespace Zhukmax\Smsc;

use GuzzleHttp\Client;
use Zhukmax\Smsc\Interfaces\BaseInterface;
use Zhukmax\Smsc\Interfaces\InformationInterface;

/**
 * Class AbstractApi
 * @package Zhukmax\Smsc
 */
abstract class AbstractApi implements BaseInterface, InformationInterface
{
    protected string $protocol;
    protected string $charset;
    protected string $from;
    protected bool $httpPost;
    protected ?string $sender;

    protected string $url;
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

    /** @var resource|bool */
    private $curl;

    /**
     * @throws \Exception
     */
    public function __construct(protected string $login, protected string $password, array $options = [])
    {
        if (!$this->login || !$this->password) {
            throw new Exception("Login and password is required");
        }

        $this->protocol = isset($options['https']) ? 'https': 'http';
        $this->charset = $options['charset'] ?? 'utf-8';
        $this->from = $options['from'] ?? 'api@smsc.ru';
        $this->httpPost = isset($options['post']);
        $this->sender = $options['sender'] ?? null;

        // Initialize GuzzleHttp client
        $this->client = new Client();

        // Initialize logger
        $this->log = new Logger($options['log'] ?? '');

        $this->url = $this->protocol . "://smsc.ru/sys/%s.php?login=" .
            urlencode($this->login) . "&psw=" . urlencode($this->password) .
            "&fmt=1&charset=" . $this->charset;
    }

    /**
     * @param int|null $id
     * @return string
     */
    protected static function format(int $id = null): string
    {
        return $id ? "&".self::$formats[$id] : "";
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
     * Формирует URL и делает 5 попыток чтения через разные подключения к сервису.
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
        $url = str_replace('%s', $cmd, $this->url);
        return $url . self::argString($props);
    }

    private function replaceUrl(string $url, int $i): string
    {
        return str_replace('://', "://www{$i}", $url);
    }

    /**
     * Функция чтения URL.
     *
     * @param $url
     * @param $files
     * @param int $tm
     * @return bool|mixed|string
     * @throws \Exception
     */
    protected function readUrl($url, $files, int $tm = 5): mixed
    {
        $post = $this->httpPost || strlen($url) > 2000 || $files;
        $result = "";

        if (function_exists("curl_init")) {
            $this->initCurl($tm);
            curl_setopt($this->curl, CURLOPT_POST, $post);

            if ($post) {
                list($url, $post) = explode("?", $url, 2);

                if ($files) {
                    parse_str($post, $m);

                    foreach ($m as $k => $v) {
                        $m[$k] = isset($v[0]) && $v[0] == "@" ? sprintf("\0%s", $v) : $v;
                    }

                    $post = $m;
                    foreach ($files as $i => $path) {
                        if (file_exists($path)) {
                            $post["file" . $i] = function_exists("curl_file_create") ? curl_file_create($path) : "@" . $path;
                        }
                    }
                }

                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post);
            }

            curl_setopt($this->curl, CURLOPT_URL, $url);

            $result = curl_exec($this->curl);
        } else if ($files) {
            throw new \Exception("Не установлен модуль curl для передачи файлов!");
        } else if ($this->protocol === 'https' && function_exists("fsockopen")) {
            $m = parse_url($url);

            if (!$fp = fsockopen($m["host"], 80, $errno, $errstr, $tm)) {
                $fp = fsockopen("212.24.33.196", 80, $errno, $errstr, $tm);
            }

            if ($fp) {
                stream_set_timeout($fp, 60);

                fwrite($fp, ($post ? "POST $m[path]" : "GET $m[path]?$m[query]")." HTTP/1.1\r\nHost: smsc.ru\r\nUser-Agent: PHP".($post ? "\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen($m['query']) : "")."\r\nConnection: Close\r\n\r\n".($post ? $m['query'] : ""));

                while (!feof($fp)) {
                    $result .= fgets($fp, 1024);
                }
                list(, $result) = explode("\r\n\r\n", $result, 2);

                fclose($fp);
            }
        } else {
            $result = file_get_contents($url);
        }

        return $result;
    }

    /**
     * @param int $timeout
     */
    private function initCurl($timeout)
    {
        if (function_exists("curl_init") && !$this->curl) {
            $this->curl = curl_init();
            curl_setopt_array($this->curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTPHEADER => ['Expect:']
            ]);
        }
    }
}
