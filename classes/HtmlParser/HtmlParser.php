<?php

namespace Parser\HtmlParser;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use voku\helper\HtmlDomParser;

class HtmlParser
{
	/**
	 * @param $url
	 * @return bool|string
	 */

	public static function getBody($url)
	{
		$result = '';

		$client = new Client([
			"curl" => [CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13"],
			"stream" => true,
			"delay" => 50
		]);

		try {
			$response = $client->request('GET', $url);
			$body = $response->getBody();
			while (!$body->eof()) {
				$result .= $body->read(1024);
			}
			return $result;
		} catch (\Exception $e) {
			echo $e->getMessage() . PHP_EOL;
			return false;
		}
	}

	/**
	 * @param $body
	 * @return array|bool
	 */

	public static function getMorePhoto($body)
	{
		try {
			$crawler = new Crawler($body);
			$links = $crawler->filter('.element-slide-main .fancybox-thumbs')->each(function (Crawler $node) {
				return P_SITE_BASE_NAME . $node->attr('href');
			});
			unset($links[0]);
			return $links;
		} catch (\Exception $e) {
			echo $e->getMessage() . PHP_EOL;
			return false;
		}
	}

	/**
	 * @param $body
	 * @return bool|mixed
	 */

	public static function getDetailPicture($body)
	{
		try {
			$crawler = new Crawler($body);
			$links = $crawler->filter('.element-slide-main .fancybox-thumbs')->each(function (Crawler $node) {
				if (substr($node->attr('href'), 0, 4) === 'http') {
					return $node->attr('href');
				} else {
					return P_SITE_BASE_NAME . $node->attr('href');
				}
			});
			return $links[0];
		} catch (\Exception $e) {
			echo $e->getMessage() . PHP_EOL;
			return false;
		}
	}

	public static function getDescription($body)
	{
		try {
			$crawler = new Crawler($body);
			$descriptionHtml = $crawler->filter('.element-description')->html();
			return $descriptionHtml;
		} catch (\Exception $e) {
//			echo $e->getTraceAsString() . ' ' . $e->getMessage() . PHP_EOL;
			echo $e->getMessage() . PHP_EOL;
			return false;
		}
	}

	public static function parseDescription($descriptionHtml)
	{
		try {
			$parsedDescription = [];
			$crawler = new Crawler($descriptionHtml);

			$links = $crawler->filter('a')->each(function (Crawler $node) {
				return $node->attr('href');
			});

			$images = $crawler->filter('img')->each(function (Crawler $node) {
				$src = str_replace('../..', '', $node->attr('src'));
				if (substr($src, 0, 4) === 'http') {
					return $src;
				} else {
					return P_SITE_BASE_NAME . $src;
				}
			});

			$dom = new HtmlDomParser($descriptionHtml);

			// TODO удаляем ссылки, перенести в add?

			foreach ($dom->find('a') as $link) {
//				echo $link . PHP_EOL;
				if (stripos($link, '/upload/') === false) {
					$link->outertext = '';
				}
			}

			$parsedDescription["HTML"] = trim($dom->html());
			$parsedDescription["LINKS"] = $links;
			$parsedDescription["IMAGES"] = $images;

			$dom->clear();

			unset($dom);

			return $parsedDescription;

		} catch (\Exception $e) {
			echo $e->getMessage() . PHP_EOL;
			return false;
		}
	}
}
