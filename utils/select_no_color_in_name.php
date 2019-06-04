#!/usr/bin/php
<?php
if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}
require(__DIR__ . "/../config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once(__DIR__ . "/../vendor/autoload.php");
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog');

global $DB;

// Получим ID временного раздела

$itemsIds = [];
$sectionSkusFlatten = [];
$sectionSkusColors = [];

$resultDb = \CIBlockElement::GetList(
	[],
	[
		"IBLOCK_ID" => 12,
//		"IBLOCK_SECTION_ID" => 386,
		"SECTION_ID" => 386,
	],
	false,
	false,
	["ID", "IBLOCK_ID", "NAME"]);

while ($res = $resultDb->GetNext()) {
	$itemsIds[] = $res["ID"];
}

// Выбираем офферы этих товаров
$sectionSkus = \CCatalogSku::getOffersList(
	$itemsIds,
	0,
	[],
	[
		"ID",
		"NAME",
		"PARENT_ITEM_ID",
		"PROPERTY_TSVET"
	]
);

foreach ($sectionSkus as $itemKey => $itemValue) {
	foreach ($itemValue as $offerKey => $offerValue) {
		$sectionSkusFlatten[$offerKey] = $offerValue;
	}
}

foreach ($sectionSkusFlatten as $key => $value) {
	$parentProduct = \Bitrix\Catalog\ProductTable::getList([
		"filter" => ["=ID" => $value["PARENT_ID"]],
		"select" => [
			'ID',
			'NAME' => 'IBLOCK_ELEMENT.NAME'
		]
	])->fetch();

	if (stripos($parentProduct["NAME"], $value["PROPERTY_TSVET_VALUE"]) === false) {

//	    echo "{$parentProduct["ID"]} {$parentProduct["NAME"]}" . PHP_EOL;

		$DB->StartTransaction();

		if (!\CIBlockElement::Delete($parentProduct["ID"])) {
			echo "Ошибка удаления {$parentProduct["NAME"]}" . PHP_EOL;
			$DB->Rollback();
		} else {
			$DB->Commit();
			if ($parentProduct["NAME"]) {
				echo "Товар {$parentProduct["NAME"]} успешно удален" . PHP_EOL;
			}
		}
	}
}

//file_put_contents(__DIR__ . "/../logs/sectionSkusFlatten.log", print_r($sectionSkusFlatten, true));
//file_put_contents(__DIR__ . "/../logs/itemsIds.log", print_r($itemsIds, true));


