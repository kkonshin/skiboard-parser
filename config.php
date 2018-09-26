<?

ini_set("memory_limit", "256M");

set_time_limit(0);

ignore_user_abort(true);

$_SERVER["DOCUMENT_ROOT"] = "/home/bitrix/www";

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);

define("LANG", "s1");
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_BUFFER_USED", true);
define("BX_CLUSTER_GROUP", 2); // Отключает исполнение агентов

define('SOURCE', "http://b2b.skiboard.ru/yml_get/uzvev7kr159d");
define('SOURCE_SAVE_PATH', __DIR__ . "/save/");
define('SKU_IBLOCK_ID', 13);
define('HIGHLOAD_ID', 2);

?>