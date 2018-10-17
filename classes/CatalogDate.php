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
			echo "Даты в старой и новой выгрузке совпадают. Обновление каталога не требуется" . PHP_EOL;
			die();
		} else if (!empty($sourceDate)) {
			echo "Получен новый файл выгрузки. Будет произведено обновление товаров каталога" . PHP_EOL;
			return true;
		}
		return false;
	}

}