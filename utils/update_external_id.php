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




$params = new SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION);
$items = new Parser\Catalog\Items($params);

//$source = new Source(SOURCE);

//$xml = $source->getSource();

//$crawler = new Crawler($xml);

//$resultArray = ParserBody::parse($crawler);

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

//file_put_contents(__DIR__ . "/../logs/update_external__itemsList.log", print_r($itemsList, true));
//file_put_contents(__DIR__ . "/../logs/update_external__skusList.log", print_r($skusList, true));

//$skuList = $itemStatus->getSkuListWithoutParent();

 // Обновление свойства "ID ТП из прайса skiboard"

//ExternalOfferId::updateExternalOfferId($skuList, $resultArray);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");