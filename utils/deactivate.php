#!/usr/bin/php

<?php
/*
 * Скрипт деактивирует все товары во временном разделе skiboard temp. Для запуска набрать php -f deactivate.php
 * в командной строке в папке парсера
 */
if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

require(__DIR__ . "/../config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once("../vendor/autoload.php");

use Parser\SectionParams;
use Parser\ItemsStatus;
use Parser\Deactivate;

while (ob_get_level()) {
	ob_end_flush();
}

try {

	$params = new SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION);
	$itemStatus = new ItemsStatus($params);

	//Деактивация товаров

	Deactivate::deactivateItems($itemStatus);
    echo "Товары раздела " . TEMP_CATALOG_SECTION . " деактивированы" . PHP_EOL;

    // Деактивация торговых предложений

	Deactivate::deactivateSkus($itemStatus);
	echo "Торговые предложения раздела " . TEMP_CATALOG_SECTION . " деактивированы" . PHP_EOL;

} catch (Exception $e){
    echo $e->getMessage() . PHP_EOL;
}
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
