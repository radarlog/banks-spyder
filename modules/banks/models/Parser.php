<?php

namespace app\modules\banks\models;

use simple_html_dom;
use Curl\Curl;
use Yii;

class Parser
{
    public static $curl;
    public static $html;

    public function __construct()
    {
        $cookiesFile = Yii::getAlias('@runtime/banksCookies');

        self::$curl = new Curl();
        self::$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        self::$curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        self::$curl->setOpt(CURLOPT_SSL_CIPHER_LIST, 'AES128-SHA');
        self::$curl->setOpt(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_0);
        self::$curl->setCookieFile($cookiesFile);  //on read
        self::$curl->setCookieJar($cookiesFile); //on write
        self::$curl->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/43.0.2357.65 Chrome/43.0.2357.65 Safari/537.36');
        self::$curl->setTimeout(30);
        self::$curl->setOpt(CURLOPT_AUTOREFERER, true);
        self::$curl->setOpt(CURLOPT_ENCODING, 'gzip');
        self::$curl->setHeader('Expect', '');
        self::$curl->error(function ($curl) {
            throw new ParserExceprion("Curl error: {$curl->errorMessage}");
        });

        self::$html = new simple_html_dom();
    }

    public static function getJsonArray($str, $objectName)
    {
        $json[$objectName] = [];
        $pattern = "\"$objectName\":(\[(?>[^\[\]]+\])|(?R))+"; //find $objectName array
        if (preg_match("/$pattern/", $str, $m)) {
            $json = str_replace("'", '"', '{' . $m[0] . '}'); // make all quotes double and wrap with {}
            $json = json_decode($json, true);
        }
        return $json[$objectName];
    }
}