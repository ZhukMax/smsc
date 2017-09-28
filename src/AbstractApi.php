<?php
namespace Zhukmax\Smsc;

/**
 * Class AbstractApi
 * @package Zhukmax\Smsc
 */
abstract class AbstractApi
{
    /**
     * @var string
     */
    protected $login;
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $protocol;
    /**
     * @var string
     */
    protected $charset;
    /**
     * @var string
     */
    protected $from;
    /**
     * @var bool
     */
    protected $httpPost;
    /**
     * @var string
     */
    protected $debug;
    /**
     * @var string
     */
    protected $sender;
    /**
     * @var string
     */
    private $url;
    /**
     * @var
     */
    private $curl;

    /**
     * AbstractApi constructor.
     * @param string $login
     * @param string $password
     * @param array $options
     * @throws \Exception
     */
    public function __construct($login, $password, $options = [])
    {
        $this->login = $login ?: null;
        $this->password = $password ?: null;
        if (!$this->login || !$this->password) {
            throw new \Exception("Логин и пароль обязательные поля!");
        }

        $this->protocol = isset($options['https']) ? 'https': 'http';
        $this->charset = isset($options['charset']) ? $options['charset'] : 'utf-8';
        $this->from = isset($options['from']) ? $options['from'] : 'api@smsc.ru';
        $this->httpPost = isset($options['post']) ?: false;
        $this->sender = isset($options['sender']) ?: null;

        $this->setDebug($options);

        $this->url = $this->protocol . "://smsc.ru/sys/%s.php?login=" .
            urlencode($this->login) . "&psw=" . urlencode($this->password) .
            "&fmt=1&charset=" . $this->charset;
    }

    /**
     * @param string $property
     * @return mixed
     */
    abstract public function getProperty($property);

    /**
     * Функция отправки SMS.
     *
     * @param $phones
     * @param $message
     * @param int $translit
     * @param int $time
     * @param int $id
     * @param int $format
     * @param string $sender
     * @param string $query
     * @param array $files
     * @return mixed
     */
    abstract public function sendSms($phones, $message, $translit = 0, $time = 0, $id = 0, $format = 0, $sender, $query = "", $files = array());

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
    abstract public function sendSmsMail($phones, $message, $translit = 0, $time = 0, $id = 0, $format = 0, $sender = "");

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
    abstract public function getSmsCost($phones, $message, $translit = 0, $format = 0, $sender = false, $query = "");

    /**
     * Функция проверки статуса отправленного SMS или HLR-запроса.
     *
     * @param $id
     * @param $phone
     * @param int $all
     * @return mixed
     */
    abstract public function getStatus($id, $phone, $all = 0);

    /**
     * Функция получения баланса.
     *
     * @return array|bool
     */
    abstract public function getBalance();

    /**
     * Функция вызова запроса.
     * Формирует URL и делает 5 попыток чтения через разные подключения к сервису.
     *
     * @param string $cmd
     * @param string $arg
     * @param array $files
     * @return array
     */
    protected function sendCmd($cmd, $arg = "", $files = array())
    {
        $url = $_url = str_replace("%s", $cmd, $this->url) . "&" . $arg;
        $i = 0;

        do {
            if ($i++) {
                $url = str_replace('://', '://www' . $i, $_url);
            }

            $result = $this->readUrl($url, $files, 3 + $i);
        } while ($result == "" && $i < 5);

        if ($result == "") {
            if ($this->debug) {
                echo "Ошибка чтения адреса: $url\n";
            }

            $result = ",";
        }

        $delimiter = ",";

        if ($cmd == "status") {
            parse_str($arg, $m);

            if (strpos($m["id"], ",")) {
                $delimiter = "\n";
            }
        }

        return explode($delimiter, $result);
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
    protected function readUrl($url, $files, $tm = 5)
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
            if ($this->debug){
                throw new \Exception("Не установлен модуль curl для передачи файлов!");
            }
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
     * @param string $message
     */
    protected function log($message = '')
    {
        if ($this->debug) {
            $data = date("Y-m-d H:i:s") . " | " . $message . "\n";
            file_put_contents($this->debug, $data, FILE_APPEND);
        }
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

    /**
     * @param array $options
     */
    private function setDebug($options)
    {
        if (isset($options['debug']) && is_file($options['debug'])) {
            $this->debug = $options['debug'];
        } else {
            $this->debug = null;
        }
    }
}
