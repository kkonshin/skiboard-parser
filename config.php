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

define('SOURCE', "http://www.kite.ru/yaexport/yandexv2.xml");
//define('SOURCE', __DIR__ . "/save/prices_update_test/previous.xml"); // для разработки
define('SOURCE_SAVE_PATH', __DIR__ . "/save/"); // для сохранения временных файлов
define('CATALOG_IBLOCK_ID', 12);
define('SKU_IBLOCK_ID', 13);
define('HIGHLOAD_ID', 2);
//define('TEMP_CATALOG_SECTION', 392); // ID раздела для временного сохранения товаров
define('TEMP_CATALOG_SECTION', 400); // ID раздела для временного сохранения товаров
define('SIZE_PROPERTY_ID', 120); // ID свойства "Размер"
define('SIZE_PROPERTY_VALUE__ONE_SIZE', 1498); // ID значение свойства "Размер" = "Единый"

// TODO эти свойства из парсеров кроме skiboard - убрать
//define('PROPERTY_SKIBOARD_ITEM_TYPE', 214); // ID свойства "Тип" (только для skiboard.ru)
//define('PROPERTY_SKIBOARD_ITEM_PURPOSE', 215); // ID свойства "Назначение" (только для skiboard.ru)

//FIXME дубль
define('P_SITE_NAME', 'kite.ru'); // Свойство товара "Сайт" - сайт с которого происходит товар
define('P_SITE_BASE_NAME', 'https://www.kite.ru'); // Префикс для путей к картинкам донорского сайта и т.п.


$translitParams = Array(
	"max_len" => "600", // обрезает символьный код до 100 символов
	"change_case" => "L", // буквы преобразуются к нижнему регистру
	"replace_space" => "_", // меняем пробелы на нижнее подчеркивание
	"replace_other" => "_", // меняем левые символы на нижнее подчеркивание
	"delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
	"use_google" => "false", // отключаем использование google
);

define('P_TRANSLIT_PARAMS', $translitParams); // параметры транслитерации для получения символьного кода


// Свойства торговых предложений, которые не нужно создавать при записи каталога gssport.ru
$propertuesToExclude = [
	"oldId",
	"Остаток",
	"Цвет",
	"ЦветПроизводителя",
	"variation_sku",
];

define('P_PROPERTIES_TO_EXCLUDE', $propertuesToExclude);

/*
 * Связь между категориями в прайсе и разделами каталога товаров
 */

$categoryToSection = [

];

/*
 * Массив всех категорий в прайсе, требуется для отслеживания появления новых категорий
 */

$categories = array_keys($categoryToSection);

/*
 * Массив всех разделов для привязки товаров
 */

$catalogSections = [];

foreach ($categoryToSection as $sections){
	foreach ($sections as $section){
		$catalogSections[] = $section;
	}
}

$catalogSections = array_unique($catalogSections);

/*
 * Наценка устанавливается в зависимости от сезона. Значения свойства CATEGORY_ID для разнесения товаров по сезонам
 */

$summer = array_unique([

]);

$winter = array_unique([

]);

define('SUMMER', $summer);
define('WINTER', $winter);

/*
 * Свойство товара "ТИП" в зависимости от категории
 */

$itemType = [
//	"358" => "Надувные",
];

/*
 * ID значений свойства "НАЗНАЧЕНИЕ"
 * Эти значения быстро получаются при помощи get_property.php
 */

$itemTypeId = [
//	"358" => 1126,
];

/*
 * Свойство товара "НАЗНАЧЕНИЕ" в зависимости от категории
 */

$itemPurpose = [
//	"296" => "Фалы и рукоятки",
];

/*
 * ID значений свойства "НАЗНАЧЕНИЕ"
 */

$itemPurposeId = [
//	"296" => 1132,
];
