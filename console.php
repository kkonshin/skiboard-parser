#!/usr/bin/php

<?php
set_time_limit(0);
ini_set("memory_limit", "512M");

$_SERVER["DOCUMENT_ROOT"] = "/home/bitrix/www";

define("LANG", "s1");

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_BUFFER_USED", true);

require ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

while(ob_get_level()){
	ob_end_flush();
}

$startExecTime = getmicrotime();

sleep(10);

echo "\nScript works for " . (getmicrotime() - $startExecTime) . " sec\n";

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");