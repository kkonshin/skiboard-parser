#!/usr/bin/php

<?php
/*
 * Скрипт активирует все товары во временном разделе skiboard temp. Для запуска набрать php -f activate.php
 * в командной строке в папке парсера
 */
if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

require(__DIR__ . "/config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once("vendor/autoload.php");

use Parser\PropertyTypeList;

while (ob_get_level()) {
	ob_end_flush();
}

$itemType = new PropertyTypeList(PROPERTY_SKIBOARD_ITEM_TYPE);

$itemPurpose = new PropertyTypeList(PROPERTY_SKIBOARD_ITEM_PURPOSE);

print_r($itemType->getProperty());
echo "--------------------------------------------------------------" . PHP_EOL;
print_r($itemPurpose->getProperty());

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");