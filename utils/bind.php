#!/usr/bin/php

<?php

if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

require(__DIR__ . "/../config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once(__DIR__ . "/../vendor/autoload.php");

global $categoryToSection;

while (ob_get_level()) {
	ob_end_flush();
}

$params = new Parser\SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION);

$items = new Parser\Catalog\Items($params);

$itemsList = $items->getList([], ["PROPERTY_CATEGORY_ID"])->list;

$itemsIds = [];

foreach ($itemsList as $item){
    $itemsIds[] = $item["ID"];
}

$doubles = [

];

$filter = ["NAME" => $doubles];

// TODO продумать фильтр

\CIBlockElement::GetPropertyValuesArray($itemsIds,CATALOG_IBLOCK_ID, $filter);

// Только для SKIBOARD!
// Привязка всех товаров временного раздела к разделам в зависимости от таблицы $categoryToSection из файла конфигурации
//Parser\Utils\BindSections::bind($itemsList, $categoryToSection, TEMP_CATALOG_SECTION, CATALOG_IBLOCK_ID);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
