<?php

namespace Parser\XlsParser;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use League\Csv\Reader;

class XlsParser
{
    const XLS_FILE_PATH = __DIR__ . "/../../save/sklad.xls";
    const CSV_FILE_PATH = __DIR__ . "/../../save/sklad.csv";

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

        $link = self::getLink();

        if ($link === false) {
            echo "Не удалось получить ссылку на файл XLS" . PHP_EOL;
            return;
        }

        $url = parse_url($link, 5);

        $client = new Client(self::$options);

        try {
            $response = $client->request('GET', $url);
            $body = $response->getBody();
            while (!$body->eof()) {
                $result .= $body->read(1024);
            }
            file_put_contents(self::XLS_FILE_PATH, $result);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public static function convert()
    {
        try {
            self::getXls();
            if (!is_file(self::XLS_FILE_PATH)) {
                return;
            }
            exec('cd ~/www/test/skiboard-parser-new/save; xls2csv sklad.xls > sklad.csv');
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    private static function selectColumns(Array $parserResult = []){
        $ta = [];
        foreach ($parserResult as $key => $value){
            $ta[$key][] = str_pad($value[2], 11, "0", STR_PAD_LEFT); // Код поставщика
            $ta[$key][] = $value[5]; // Полное наименование
            $ta[$key][] = (stripos(trim(mb_strtolower(strval($value[14]))), "более") === false) ? $value[14] : 5; // Остаток
            $ta[$key][] = $value[15]; // Процент скидки
        }
        return $ta;
    }


    public static function parse()
    {
        $res = [];
        try {
            self::convert();
            $inputCsv = Reader::createFromPath(self::CSV_FILE_PATH);
            $inputCsv->setDelimiter(',');
            $res = $inputCsv->addFilter(function ($row, $index) {
                return $index > 1 && $row[0] != false;
            })->fetchAll();
            unset($res[count($res) - 1]);
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
        $res = self::selectColumns($res);
        return $res;
    }
}
