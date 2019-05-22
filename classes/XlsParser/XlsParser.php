<?php

namespace Parser\XlsParser;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class XlsParser
{
    const INPUT_FILE_TYPE = 'Xls';
    const FILE_PATH = __DIR__ . "/../../save/sklad.xls";
    private static $options = [
        "base_uri" => "http://skiboard.ru/",
        "curl" => [
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13"
        ],
        "stream" => true,
        "delay" => 50,
        "headers" => []
    ];


    public static function getLink()
    {
        $result = '';
        $url = 'sklad';

        $client = new Client(self::$options);

        try {
            $response = $client->request('GET', $url);
            $body = $response->getBody();
            while (!$body->eof()) {
                $result .= $body->read(1024);
            }
            $crawler = new Crawler($result);
            $link = $crawler->filter('.link-5.w-button')->attr('href');
            return $link;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            return false;
        }
    }

    public static function getXls()
    {
        $result = '';
        $url = parse_url(self::getLink(), 5);
        echo self::FILE_PATH;
        $client = new Client(self::$options);
        try {
            $response = $client->request('GET', $url);
            $body = $response->getBody();
            while (!$body->eof()) {
                $result .= $body->read(1024);
            }
            file_put_contents(self::FILE_PATH, $result);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public static function parse()
    {
        try {

            self::getXls();

            if(!is_file(self::FILE_PATH)){
                return;
            }

            exec('cd ../save; xls2csv sklad.xls > sklad.csv');

        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }
}
