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

// Наценка устанавливается в зависимости от сезона. Значения свойства CATEGORY_ID для разнесения товаров по сезонам

$summer = array_unique([
	288, 289, 290, 418, 321, 322, 296, 398, 400, 411, 412, 413, 414, 415, 275, 330, 328, 329, 331, 332, 333,
	334, 335, 336, 396, 294, 358, 360, 359, 361, 362, 389, 292, 401, 385, 393, 381, 370, 278, 279, 282, 283,
	368, 409, 372, 347, 368, 409, 372, 347, 348, 349, 327, 350, 351, 352, 353, 354, 355, 356, 357, 271, 419,
	297, 298, 299, 300, 301, 302, 303, 325, 326, 402, 403, 404, 405, 406, 371, 270, 310, 304, 305, 306, 307,
	386, 387, 410, 395, 420, 421, 422, 423, 424, 425, 383, 392, 293, 427,
]);


$winter = array_unique([
	337, 338, 339, 340, 341, 342, 343, 407, 385, 399, 416, 417, 273, 280, 286, 287, 408, 369, 365, 266, 365,
	373, 280, 286, 287, 408, 369, 267, 268, 269, 364, 391
]);



?>