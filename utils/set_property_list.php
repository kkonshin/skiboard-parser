#!/usr/bin/php

<?php
if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

/**
 * Обновление значений свойств типа список
 * Cюда передавать ID товара, ID инфоблока товаров, массив вида ID свойства => ID значения свойства
 */

require(__DIR__ . "/../config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once(__DIR__ . "/../vendor/autoload.php");

use Parser\PropertyTypeList;

while (ob_get_level()) {
	ob_end_flush();
}

$itemType = new PropertyTypeList(PROPERTY_SKIBOARD_ITEM_TYPE);

$itemPurpose = new PropertyTypeList(PROPERTY_SKIBOARD_ITEM_PURPOSE);



$itemType->setPropertyValues(109072, CATALOG_IBLOCK_ID, [PROPERTY_SKIBOARD_ITEM_TYPE => 20789]);
$itemPurpose->setPropertyValues(109072, CATALOG_IBLOCK_ID, [PROPERTY_SKIBOARD_ITEM_PURPOSE => 20785]);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");