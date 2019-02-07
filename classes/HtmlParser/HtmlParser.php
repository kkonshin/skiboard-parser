<?php

namespace Parser\HtmlParser;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class HtmlParser
{

	// TODO убрать Goutte из зависимостей если не понадобится

	public static function getBody($url)
	{
		$result = '';

		$client = new Client([
			"curl" => [CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13"],
			"stream" => true,
			"delay" => 100
		]);

		try {
			$response = $client->request('GET', $url);
			$body = $response->getBody();

			while (!$body->eof()) {
				$result .= $body->read(1024);
			}

			return $result;

		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	public static function getDetailPicture($body)
	{
		try {
			$pictureUrl = '';
			$crawler = new Crawler($body);
			$pictureUrl = $crawler->filter('.element-slide-main img')->attr('src');
//			echo $pictureUrl . PHP_EOL;
		} catch (\Exception $e) {
			echo $e->getMessage() . PHP_EOL;
		}
		return $pictureUrl;
	}
}