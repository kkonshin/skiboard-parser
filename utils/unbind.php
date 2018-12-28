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

use Parser\SectionParams;
use Parser\ItemsStatus;
use Parser\BindToSections;

while (ob_get_level()) {
	ob_end_flush();
}

$params = new SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION);

$itemStatus = new ItemsStatus($params);

/*
 * Отвязывает товары во временном разделе от всех остальных разделов
 */

BindToSections::bind($itemStatus, []);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");