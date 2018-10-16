<?php

namespace Parser;

use Symfony\Component\DomCrawler\Crawler;

class CatalogDate
{
	public static function checkDate(Crawler $crawler, Crawler $previousCrawler)
	{

		$sourceDate = $crawler->filter('yml_catalog')->attr('date');

		$previousSourceDate = $previousCrawler->filter('yml_catalog')->attr('date');

		if ($sourceDate === $previousSourceDate) {
			echo "Обновление каталога не требуется" . PHP_EOL;
			die();
		} else if (!empty($sourceDate)) {
			echo "Будет произведено обновление товаров каталога" . PHP_EOL;
			return true;
		}
		return false;
	}

}