<?
ini_set("memory_limit", "256M");
set_time_limit(0);
ignore_user_abort(true);

$_SERVER["DOCUMENT_ROOT"] = "/home/bitrix/www";
// Показ ошибок
// Вывод некоторых ошибок требует установки 'debug' => true в /bitrix/.settings.php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED);
//ini_set('log_errors', 1); // Логирование ошибок в файл
//ini_set('error_log', __DIR__ . "/logs/errors.log"); // Адрес лога ошибок

define("LANG", "s1");
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_BUFFER_USED", true);
define("BX_CLUSTER_GROUP", 2); // Отключает исполнение агентов
define('SOURCE', "https://www.gssport.ru/beeshop/data-exchange/export_for_dillers/diller2");
define('SOURCE_SAVE_PATH', __DIR__ . "/save/"); // для сохранения временных файлов
define('CATALOG_IBLOCK_ID', 12);
define('SKU_IBLOCK_ID', 13);
define('HIGHLOAD_ID', 2);
define('TEMP_CATALOG_SECTION', 386); // ID раздела для временного сохранения товаров
define('SIZE_PROPERTY_ID', 120); // ID свойства "Размер"
define('PROPERTY_SKIBOARD_ITEM_TYPE', 214); // ID свойства "Тип"
define('PROPERTY_SKIBOARD_ITEM_PURPOSE', 215); // ID свойства "Назначение"
define('P_SITE_NAME', 'gssport.ru'); // Свойство товара "Сайт" - сайт с которого происходит товар

$translitParams = Array(
	"max_len" => "600", // обрезает символьный код до 100 символов
	"change_case" => "L", // буквы преобразуются к нижнему регистру
	"replace_space" => "_", // меняем пробелы на нижнее подчеркивание
	"replace_other" => "_", // меняем левые символы на нижнее подчеркивание
	"delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
	"use_google" => "false", // отключаем использование google
);

define('P_TRANSLIT_PARAMS', $translitParams);

// Свойства торговых предложений, которые не нужно создавать при записи каталога gssport.ru
// TODO проверить существование
$propertiesToExclude = [
	"oldId",
	"Остаток",
	"Цвет",
	"ЦветПроизводителя",
	"variation_sku",
];

define('P_PROPERTIES_TO_EXCLUDE', $propertiesToExclude);
