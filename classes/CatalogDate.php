<?php

namespace Parser;

use Symfony\Component\DomCrawler\Crawler;

class CatalogDate
{
	/**
	 * Метод сравнивает дату и время в новом и предыдущем файле XML
	 * @param Crawler $crawler
	 * @param Crawler $previousCrawler
	 * @return bool
	 */
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