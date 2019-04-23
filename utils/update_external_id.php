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

//$source = __DIR__ . "/../save/backup/13.02.2019/previous.xml";
$source = SOURCE;

$tempCatalogSection = 392;

$params = new SectionParams(CATALOG_IBLOCK_ID, $tempCatalogSection);

$items = new Parser\Catalog\Items($params);

$source = new Source($source);

$xml = $source->getSource();

$crawler = new Crawler($xml);

$resultArray = ParserBody::parse($crawler);

// TODO
// берем старый previous.xml в качестве источника,
// старый раздел в качестве цели
// пишем товарам и ТП P_GROUP_ID и P_KITERU_EXTERNAL_OFFER_ID
$extraProperties = [
	"PROPERTY_P_GROUP_ID",
];
$itemsList = $items->getList([], $extraProperties)->list;
$skusList = $items->getList()
    ->getItemsIds()
    ->getSkusList(["CODE" => ["P_KITERU_EXTERNAL_OFFER_ID"]])
    ->getSkusListFlatten()
    ->skusListFlatten;

ExternalOfferId::updateExternalItemId($itemsList, $resultArray, "P_GROUP_ID", P_TRANSLIT_PARAMS);

ExternalOfferId::updateExternalOfferId($skusList, $resultArray, "P_KITERU_EXTERNAL_OFFER_ID");

//file_put_contents(__DIR__ . "/../logs/update_external__resultArray--392.log", print_r($resultArray, true));
//file_put_contents(__DIR__ . "/../logs/update_external__itemsList--392.log", print_r($itemsList, true));
//file_put_contents(__DIR__ . "/../logs/update_external__skusList--392.log", print_r($skusList, true));

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
