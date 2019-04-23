#!/usr/bin/php

<?php

if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

// Установка количества ТП = 0, если у родительского товара отстутствует привязка к XML и он не может обновляться

require(__DIR__ . "/../config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once(__DIR__ . "/../vendor/autoload.php");

use Parser\SectionParams;
use Parser\Source\Source;
use Parser\Utils\ExternalOfferId;
use Parser\ParserBody\ParserBody;
use Symfony\Component\DomCrawler\Crawler;

while (ob_get_level()) {
	ob_end_flush();
}

$tempCatalogSection = 392;

$params = new SectionParams(CATALOG_IBLOCK_ID, $tempCatalogSection);

$items = new Parser\Catalog\Items($params);

$skusToSetZeroArray = [];

$extraFilter = [
	"PROPERTY_P_GROUP_ID" => false
];

$extraProperties = [
	"PROPERTY_P_GROUP_ID",
];
$itemsList = $items->getList($extraFilter, $extraProperties)->list;
$skusList = $items->getList($extraFilter, $extraProperties)
	->getItemsIds()
	->getSkusList(["CODE" => ["P_KITERU_EXTERNAL_OFFER_ID"]])
	->getSkusListFlatten()
	->skusListFlatten;

foreach ($skusList as $key => $value){
	\CCatalogProduct::Update($key, ["QUANTITY" => 0]);
	echo $value["NAME"] . PHP_EOL;
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
