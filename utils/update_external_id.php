#!/usr/bin/php

<?php
/*
 * Скрипт активирует все товары во временном разделе skiboard temp. Для запуска набрать php -f activate.php
 * в командной строке в папке парсера
 */
if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

require(__DIR__ . "/../config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once(__DIR__ . "/../vendor/autoload.php");

use Parser\SectionParams;
use Parser\ItemsStatus;
use Parser\Source\Source;
use Parser\Utils\ExternalOfferId;
use Parser\ParserBody\ParserBody;
use Symfony\Component\DomCrawler\Crawler;

while (ob_get_level()) {
	ob_end_flush();
}

// TODO для всех офферов каталога записать значения $params = new SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION);
$params = new SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION);

$itemStatus = new ItemsStatus($params);

$source = new Source(SOURCE);

$xml = $source->getSource();

$crawler = new Crawler($xml);

$resultArray = ParserBody::parse($crawler);

$skuList = $itemStatus->getSkuListWithoutParent();

//file_put_contents(__DIR__. "/../logs/skuList.log", print_r($skuList, true));

/*
foreach ($resultArray as $resultKey => $resultValue){
	foreach ($resultValue as $offerKey => $offerValue){
		foreach ($skuList as $skuKey => $skuValue){
			if ($skuValue["NAME"] === $offerValue["NAME"] . " " . $offerValue["ATTRIBUTES"]["Размер"] . " " . $offerValue["ATTRIBUTES"]["Артикул"]){

				ExternalOfferId::update($skuValue["ID"], 0, ["SKIBOARD_EXTERNAL_OFFER_ID" => [$offerValue["OFFER_ID"]]]);

			}
		}
	}
}
*/


ExternalOfferId::updateExternalOfferId($skuList, $resultValue);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");