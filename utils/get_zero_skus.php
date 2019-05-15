#!/usr/bin/php

<?php

if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

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

\Bitrix\Main\Loader::includeModule('catalog');

//$tempCatalogSection = 392;
$tempCatalogSection = TEMP_CATALOG_SECTION;

$params = new SectionParams(CATALOG_IBLOCK_ID, $tempCatalogSection);

$items = new Parser\Catalog\Items($params);

$extraFilter = [
	"PROPERTY_P_SKIBOARD_GROUP_ID" => false
];

$extraProperties = [
	"PROPERTY_P_SKIBOARD_GROUP_ID",
];

//$itemsList = $items->getList($extraFilter, $extraProperties)->list;

$skusList = $items->getList($extraFilter, $extraProperties)
	->getItemsIds()
	->getSkusList(["CODE" => ["SKIBOARD_EXTERNAL_OFFER_ID", "QUANTITY"]])
	->getSkusListFlatten()
	->skusListFlatten;

$skusIds = [];

foreach ($skusList as $sku){
	$skusIds[] = $sku["ID"];
}

$dbRes = \CCatalogProduct::GetList(
	[],
	[
		"ID"=> $skusIds,
		"QUANTITY" => false
	],
	false,
	false,
	[
		"ID",
		"ELEMENT_NAME",
		"QUANTITY"
	]
);

$ta = [];
while($res = $dbRes->GetNext()){
	$ta[] = $res;
}

//file_put_contents(__DIR__ . "/../logs/getZeroSkus__ta.log", print_r($ta, true));
//file_put_contents(__DIR__ . "/../logs/getZeroSkus__countTa.log", print_r(count($ta), true));

$items->reset();

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
