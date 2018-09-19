<?

ini_set('max_execution_time', '2400');
ini_set('session.gc_maxlifetime', 2400);
ini_set("memory_limit", "1024M");
set_time_limit(0);

$_SERVER["DOCUMENT_ROOT"] = "/home/bitrix/www";

$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

//ini_set('display_errors', 1);
//ini_set('error_reporting', E_ALL);

define("LANG", "s1");
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_BUFFER_USED", true);

define('SOURCE', "http://b2b.skiboard.ru/yml_get/uzvev7kr159d");
define('SAVE_FILE', __DIR__ . "/save/parser_dump.php");
define('SKU_IBLOCK_ID', 13);
define('HIGHLOAD_ID', 2);

?>