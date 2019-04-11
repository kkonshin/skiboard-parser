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
use Parser\Activate;

while (ob_get_level()) {
	ob_end_flush();
}

$params = new SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION);

$itemStatus = new ItemsStatus($params);
/*
 * Активация товаров
 */
Activate::activateItems($itemStatus);
/*
 * Активация торговых предложений
 */
Activate::activateSkus($itemStatus);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");