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

// TODO вынести ID свойств в конфиг

/*
 * Для рабочего сайта
 */

//$itemType = new PropertyTypeList(214);

//$itemPurpose = new PropertyTypeList(215);

/*
 * Для тестового сайта
 */

$itemType = new PropertyTypeList(244);

$itemPurpose = new PropertyTypeList(245);

print_r($itemType->getProperty());

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");