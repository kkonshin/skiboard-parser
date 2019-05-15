#!/usr/bin/php

<?php
/*
 * Скрипт активирует все товары во временном разделе текущего парсера. Для запуска набрать php -f activate.php
 * в командной строке в папке парсера
 */
if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

require(__DIR__ . "/../config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once("../vendor/autoload.php");

use Parser\SectionParams;
use Parser\Catalog\Items;

while (ob_get_level()) {
	ob_end_flush();
}

//$tempCatalogSection = 392;
$tempCatalogSection = TEMP_CATALOG_SECTION;

try {
	$params = new SectionParams(CATALOG_IBLOCK_ID, $tempCatalogSection);

	$items = new Items($params);

	$itemsList = $items->getList()->list;

	$items->reset();

	$skusList = $items
		->getList()
		->getItemsIds()
		->getSkusList()
		->getSkusListFlatten()
		->skusListFlatten;

	//Активация товаров
	Parser\Utils\Activate::activateItems($itemsList);
	echo "Товары раздела " . TEMP_CATALOG_SECTION . " активированы" . PHP_EOL;

	//Активация торговых предложений
	Parser\Utils\Activate::activateSkus($skusList);
	echo "Торговые предложения раздела " . TEMP_CATALOG_SECTION . " активированы" . PHP_EOL;

} catch (Exception $e) {
	echo $e->getMessage() . PHP_EOL;
} finally {
    $items->reset();
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
