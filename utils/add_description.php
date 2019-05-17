#!/usr/bin/php

<?php
/*
 * Скрипт обновляет детальные описания товаров во временном разделе
 */
if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

require(__DIR__ . "/../config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once(__DIR__ . "/../vendor/autoload.php");

use Parser\SectionParams;
use Parser\Source\Source;
use Parser\Utils\Description;
use Parser\ParserBody\ParserBody;
use Parser\HtmlParser\HtmlParser;
use Symfony\Component\DomCrawler\Crawler;

while (ob_get_level()) {
	ob_end_flush();
}

$params = new SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION);
$items = new Parser\Catalog\Items($params);
$source = new Source(SOURCE);
$xml = $source->getSource();
$crawler = new Crawler($xml);
$resultArray = ParserBody::parse($crawler);
foreach ($resultArray as $key => $value) {
	foreach ($value as $k => $v) {
		if (empty($v["URL"])){
			continue;
		}
		$body = HtmlParser::getBody($v["URL"]);
		if (!empty($body)) {
			$resultArray[$key][$k]["HTML_DESCRIPTION"] = HtmlParser::getDescription($body);
			if (!empty($resultArray[$key][$k]["HTML_DESCRIPTION"])) {
				$resultArray[$key][$k]["HTML_PARSED_DESCRIPTION"] = HtmlParser::parseDescription($resultArray[$key][$k]["HTML_DESCRIPTION"]);
			}
		}
	}
}

file_put_contents(__DIR__ . "/../logs/add_description--resultArray.log", print_r($resultArray, true));

// Последний параметр задает использование HTML_PARSED_DESCRIPTION вместо DESCRIPTION
//Description::updateDescription($itemStatus, $resultArray, true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
