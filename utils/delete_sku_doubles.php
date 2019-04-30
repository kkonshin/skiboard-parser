#!/usr/bin/php

<?php

if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

require(__DIR__ . "/../config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once(__DIR__ . "/../vendor/autoload.php");

while (ob_get_level()) {
	ob_end_flush();
}


$params = new Parser\SectionParams(CATALOG_IBLOCK_ID, $tempCatalogSection);

$catalogItems = new Parser\Catalog\Items($params);

$params = [
	"IBLOCK_ID" => CATALOG_IBLOCK_ID,
	"SECTION_ID" => TEMP_CATALOG_SECTION
];

$catalogSkus = $catalogItems->getList($params)
	->getItemsIds()
	->getSkusList(["CODE" => ["SKIBOARD_EXTERNAL_OFFER_ID"]])
	->getSkusListFlatten()
	->skusListFlatten;

$catalogSkusCount = count($catalogSkus);

$externalIdsArray = [];

foreach ($catalogSkus as $key => $sku) {
	if (!empty($sku["PROPERTIES"]["SKIBOARD_EXTERNAL_OFFER_ID"]["VALUE"])) {
		$externalIdsArray[$key] = $sku["PROPERTIES"]["SKIBOARD_EXTERNAL_OFFER_ID"]["VALUE"];
	}
}

// Получим массив повторяющихся значений
function array_not_unique($input)
{
	$duplicates = [];
	$processed = [];

	foreach ($input as $key => $item) {
		if (in_array($item, $processed)) {
			$duplicates[$key] = $item;
		} else {
			$processed[$key] = $item;
		}
	}
	return $duplicates;
}

$externalIdsDiffArray = array_not_unique($externalIdsArray);
//file_put_contents(__DIR__ . "/../logs/externalIdsDiffArray.log", print_r($externalIdsDiffArray, true));
// удаляем торговые предложения с ключами этого массива
foreach ($externalIdsDiffArray as $key => $value) {
	$res = \CIBlockElement::Delete($key);
	if($res){
		echo "Торговое предложение {$key} дублируется и было успешно удалено, либо не существует" . PHP_EOL;
	}
}

$catalogItems->reset();
