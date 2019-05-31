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

$source = SOURCE;

$tempCatalogSection = TEMP_CATALOG_SECTION;

$params = new SectionParams(CATALOG_IBLOCK_ID, $tempCatalogSection);

$items = new Parser\Catalog\Items($params);

$source = new Source($source);

$xml = $source->getSource();

$crawler = new Crawler($xml);

$resultArray = ParserBody::parse($crawler);

$site = P_SITE_NAME;
$codeItem = "P_GSSPORT_GROUP_ID";
$codeOffer = "P_GSSPORT_EXTERNAL_OFFER_ID";

// Проверяем наличие и, если свойства нет, создаем свойство каталога для связи товара с XML
Parser\Catalog\Properties::createExternalItemIdProperty(
	[
		"NAME" => "Идентификатор товара в каталоге {$site}",
		"CODE" => $codeItem,
        "IBLOCK_ID" => CATALOG_IBLOCK_ID
	]
);

Parser\Catalog\Properties::createExternalItemIdProperty(
	[
		"NAME" => "Идентификатор торгового предложения в каталоге gssport.ru",
		"CODE" => "P_GSSPORT_EXTERNAL_OFFER_ID",
		"IBLOCK_ID" => SKU_IBLOCK_ID
	]
);

$extraProperties = [
	"PROPERTY_{$codeItem}",
];

$itemsList = $items->getList([], $extraProperties)->list;

$items->reset();

$skusList = $items->getList()
	->getItemsIds()
	->getSkusList(["CODE" => [$codeOffer]])
	->getSkusListFlatten()
	->skusListFlatten;

ExternalOfferId::updateExternalItemId($itemsList, $resultArray, $codeItem, P_TRANSLIT_PARAMS);
ExternalOfferId::updateExternalOfferId($skusList, $resultArray, $codeOffer);

//file_put_contents(__DIR__ . "/../logs/update_external__resultArray--392.log", print_r($resultArray, true));
//file_put_contents(__DIR__ . "/../logs/update_external__itemsList--392.log", print_r($itemsList, true));
//file_put_contents(__DIR__ . "/../logs/update_external__skusList--392.log", print_r($skusList, true));

$items->reset();

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
