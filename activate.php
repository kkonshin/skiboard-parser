#!/usr/bin/php

<?php

if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

require(__DIR__ . "/config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once("vendor/autoload.php");

use Parser\SectionParams;
use Parser\ItemsStatus;
use Parser\Deactivate;
use Parser\Activate;

while (ob_get_level()) {
	ob_end_flush();
}

$params = new SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION);

$itemStatus = new ItemsStatus($params);

//Deactivate::deactivateItems($itemStatus);
Activate::activateItems($itemStatus);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");