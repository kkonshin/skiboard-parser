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

define('SOURCE', "http://b2b.skiboard.ru/yml_get/uzvev7kr159d");
//define('SOURCE', __DIR__ . "/save/previous_.xml"); // для разработки
define('SOURCE_SAVE_PATH', __DIR__ . "/save/"); // для сохранения временных файлов
define('CATALOG_IBLOCK_ID', 12);
define('SKU_IBLOCK_ID', 13);
define('HIGHLOAD_ID', 2);
define('TEMP_CATALOG_SECTION', 361); // ID раздела для временного сохранения товаров
define('SIZE_PROPERTY_ID', 120); // ID свойства "Размер"
define('PROPERTY_SKIBOARD_ITEM_TYPE', 214); // ID свойства "Тип"
define('PROPERTY_SKIBOARD_ITEM_PURPOSE', 215); // ID свойства "Назначение"

$translitParams = Array(
	"max_len" => "600", // обрезает символьный код до 100 символов
	"change_case" => "L", // буквы преобразуются к нижнему регистру
	"replace_space" => "_", // меняем пробелы на нижнее подчеркивание
	"replace_other" => "_", // меняем левые символы на нижнее подчеркивание
	"delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
	"use_google" => "false", // отключаем использование google
);

/*
 * Связь между категориями в прайсе и разделами каталога товаров
 */

$categoryToSection = [
	"289" => [175],
	"290" => [176],
	"418" => [342],
	"321" => [130],
	"322" => [178],
	"296" => [177],
	"400" => [154],
	"412" => [362],
	"413" => [363],
	"414" => [364],
	"415" => [364],
	"330" => [366],
	"328" => [367],
	"329" => [368],
	"331" => [369],
	"332" => [148],
	"333" => [370],
	"334" => [371],
	"335" => [127],
	"336" => [372],
	"396" => [373],
	"294" => [254],
	"358" => [254],
	"360" => [254],
	"359" => [255],
	"361" => [363],
	"362" => [363],
	"337" => [106, 112],
	"338" => [115],
	"339" => [107, 113],
	"340" => [132],
	"341" => [167],
	"342" => [106],
	"343" => [112],
	"407" => [374],
	"292" => [362],
	"401" => [362],
	"393" => [151],
	"381" => [364],
	"370" => [137],
	"273" => [110],
	"279" => [153],
	"282" => [153],
	"283" => [364],
	"368" => [364],
	"409" => [154],
	"347" => [131],
	"348" => [131],
	"349" => [375],
	"350" => [371],
	"351" => [127],
	"352" => [129],
	"353" => [132],
	"354" => [167],
	"355" => [165],
	"356" => [137],
	"357" => [372],
	"286" => [377],
	"287" => [378],
	"369" => [379],
	"365" => [312],
	"373" => [167],
	"267" => [380],
	"268" => [118],
	"269" => [119],
	"271" => [123],
	"391" => [381],
	"298" => [147],
	"299" => [264],
	"300" => [382],
	"301" => [221],
	"302" => [148],
	"303" => [131],
	"371" => [129],
	"270" => [130],
	"304" => [239],
	"305" => [239],
	"306" => [239],
	"386" => [383],
	"392" => [384],
	"293" => [385],
	"366" => [364]
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
	288, 289, 290, 418, 321, 322, 296, 398, 400, 411, 412, 413, 414, 415, 275, 330, 328, 329, 331, 332, 333,
	334, 335, 336, 396, 294, 358, 360, 359, 361, 362, 389, 292, 401, 385, 393, 381, 370, 278, 279, 282, 283,
	368, 409, 372, 347, 368, 409, 372, 347, 348, 349, 327, 350, 351, 352, 353, 354, 355, 356, 357, 419,
	297, 298, 299, 300, 301, 302, 303, 325, 326, 402, 403, 404, 405, 406, 371, 270, 310, 304, 305, 306, 307,
	386, 387, 410, 395, 420, 421, 422, 423, 424, 425, 383, 392, 293, 427, 366
]);

$winter = array_unique([
	337, 338, 339, 340, 341, 342, 343, 407, 385, 399, 416, 417, 273, 280, 286, 287, 408, 369, 365, 266, 365,
	373, 280, 286, 287, 408, 369, 267, 268, 269, 364, 391, 271
]);

define('SUMMER', $summer);
define('WINTER', $winter);

/*
 * Свойство товара "ТИП" в зависимости от категории
 */

$itemType = [
	"358" => "Надувные",
	"360" => "Жесткие",
	"292" => "Вейксерф",
	"401" => "Аксесcуары",
	"279" => "Парные",
	"282" => "Слаломные",
];


/*
 * ID значений свойства "НАЗНАЧЕНИЕ"
 * Эти значения быстро получаются при помощи get_property.php
 */

$itemTypeId = [
	"358" => 1126,
	"360" => 1127,
	"292" => 1128,
	"401" => 1129,
	"279" => 1130,
	"282" => 1131
];

/*
 * Свойство товара "НАЗНАЧЕНИЕ" в зависимости от категории
 */

$itemPurpose = [
	"400" => "Фалы и рукоятки",
	"414" => "Водные лыжи",
	"415" => "Надувные буксируемые баллоны",
	"381" => "Надувные буксируемые баллоны",
	"370" => "Надувные буксируемые баллоны",
	"283" => "Водные лыжи",
	"366" => "Водные лыжи",
	"368" => "Водные лыжи"
];

/*
 * ID значений свойства "НАЗНАЧЕНИЕ"
 */

$itemPurposeId = [
	"400" => 1132,
	"414" => 1133,
	"415" => 1134,
	"381" => 1134,
	"370" => 1134,
	"283" => 1135,
	"366" => 1135,
	"368" => 1135
];
