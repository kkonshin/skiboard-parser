#!/usr/bin/php;
<?php
if (php_sapi_name() !== "cli") {
    die ('Этот скрипт предназначен для запуска из командной строки');
}
//require(__DIR__ . "/../config.php");
//require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once(__DIR__ . "/../vendor/autoload.php");

Parser\XlsParser\XlsParser::parse();
