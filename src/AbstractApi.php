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
    protected $protocol;
    /**
     * @var string
     */
    protected $charset;
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
    protected $from;
    /**
     * @var bool
     */
    protected $httpPost;
    /**
     * @var bool
     */
    protected $debug;

    /**
     * AbstractApi constructor.
     * @param array $options
     */
    public function __construct($options)
    {
        $this->protocol = $options['protocol'] ?: 'http';
        $this->charset = $options['charset'] ?: 'utf-8';
        $this->login = $options['login'] ?: '';
        $this->password = $options['password'] ?: '';
        $this->from = $options['from'] ?: 'api@smsc.ru';
        $this->httpPost = $options['post'] ?: false;
        $this->debug = $options['debug'] ?: false;
    }

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
        $url = $this->protocol . "://smsc.ru/sys/$cmd.php?login=".urlencode($this->login)."&psw=".urlencode($this->password)."&fmt=1&charset=".$this->charset."&".$arg;

        $i = 0;
        do {
            if ($i++)
                $url = str_replace('://smsc.ru/', '://www'.$i.'.smsc.ru/', $url);

            $ret = $this->readUrl($url, $files, 3 + $i);
        }
        while ($ret == "" && $i < 5);

        if ($ret == "") {
            if ($this->debug) {
                echo "Ошибка чтения адреса: $url\n";
            }

            $ret = ",";
        }

        $delim = ",";

        if ($cmd == "status") {
            parse_str($arg, $m);

            if (strpos($m["id"], ","))
                $delim = "\n";
        }

        return explode($delim, $ret);
    }

    /**
     * Функция чтения URL.
     *
     * @param $url
     * @param $files
     * @param int $tm
     * @return bool|mixed|string
     */
    protected function readUrl($url, $files, $tm = 5)
    {
        $ret = "";
        $post = $this->httpPost || strlen($url) > 2000 || $files;

        if (function_exists("curl_init")) {
            static $c = 0; // keepalive

            if (!$c) {
                $c = curl_init();
                curl_setopt_array($c, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => $tm,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_HTTPHEADER => ['Expect:']
                ]);
            }

            curl_setopt($c, CURLOPT_POST, $post);

            if ($post) {
                list($url, $post) = explode("?", $url, 2);

                if ($files) {
                    parse_str($post, $m);

                    foreach ($m as $k => $v)
                        $m[$k] = isset($v[0]) && $v[0] == "@" ? sprintf("\0%s", $v) : $v;

                    $post = $m;
                    foreach ($files as $i => $path)
                        if (file_exists($path))
                            $post["file".$i] = function_exists("curl_file_create") ? curl_file_create($path) : "@".$path;
                }

                curl_setopt($c, CURLOPT_POSTFIELDS, $post);
            }

            curl_setopt($c, CURLOPT_URL, $url);

            $ret = curl_exec($c);
        } else if ($files) {
            if ($this->debug){
                echo "Не установлен модуль curl для передачи файлов\n";
            }
        } else if ($this->protocol === 'https' && function_exists("fsockopen")) {
            $m = parse_url($url);

            if (!$fp = fsockopen($m["host"], 80, $errno, $errstr, $tm))
                $fp = fsockopen("212.24.33.196", 80, $errno, $errstr, $tm);

            if ($fp) {
                stream_set_timeout($fp, 60);

                fwrite($fp, ($post ? "POST $m[path]" : "GET $m[path]?$m[query]")." HTTP/1.1\r\nHost: smsc.ru\r\nUser-Agent: PHP".($post ? "\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen($m['query']) : "")."\r\nConnection: Close\r\n\r\n".($post ? $m['query'] : ""));

                while (!feof($fp))
                    $ret .= fgets($fp, 1024);
                list(, $ret) = explode("\r\n\r\n", $ret, 2);

                fclose($fp);
            }
        } else {
            $ret = file_get_contents($url);
        }

        return $ret;
    }
}
