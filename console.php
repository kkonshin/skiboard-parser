#!/usr/bin/php

<?php

if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

require(__DIR__ . "/config.php");  // настройки и константы

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// Очищаем все 3 буфера
while (ob_get_level()) {
	ob_end_flush();
}

$startExecTime = getmicrotime();

require_once("vendor/autoload.php");

use Symfony\Component\DomCrawler\Crawler;
use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock as HL;

global $USER;

if (!is_dir(__DIR__ . "/logs")) {
	mkdir(__DIR__ . "/logs", 0777, true);
}
if (!is_dir(__DIR__ . "/save")) {
	mkdir(__DIR__ . "/save", 0777, true);
}

//-------------------------------------------------ПАРСЕР-------------------------------------------------------------//

if (!Loader::includeModule('iblock')) {
	die('Не удалось загрузить модуль инфоблоки');
}

if (!Loader::includeModule('catalog')) {
	die('Невозможно загрузить модуль торгового каталога');
}

$xml = file_get_contents(SOURCE);

$previousSourceName = "previous.xml";
$previousSourceDate = "";

if (!is_file(SOURCE_SAVE_PATH . $previousSourceName)){
	echo "Сохраняем каталог во временный файл" . PHP_EOL;
	file_put_contents(SOURCE_SAVE_PATH . $previousSourceName, $xml);

	// require (__DIR__ . "/add.php");

} else {
	$previousXml = file_get_contents(SOURCE_SAVE_PATH . $previousSourceName);
	$previousCrawler = new Crawler($previousXml);
	$previousSourceDate = $previousCrawler->filter('yml_catalog')->attr('date');
	echo "Найден сохраненный каталог от " . $previousSourceDate . PHP_EOL;
}

// TODO разделяем парсинг, запись свойств, запись элементов, апдейт свойств (?), апдейт элементов

function parse()
{
    // TODO передавать аргументами ?
    global $xml;
    global $previousSourceDate;

	$ta = [];

	$crawler = new Crawler($xml);
	// Дата свежего каталога
	$sourceDate = $crawler->filter('yml_catalog')->attr('date');

	if ($sourceDate === $previousSourceDate) {
		die ("Обновление каталога не требуется" . PHP_EOL);
	}

	// TODO если предыдущий каталог существует и даты не совпадают - запускаем сравнение и update

    // TODO выносим сохранение в отдельный скрипт

	echo "Каталог от " . $sourceDate . PHP_EOL;

	$offers = $crawler->filter('offer');

	$parentItemsIdsArray = [];

	$groupedItemsArray = [];

	try {
		// Все параметры всех офферов
		$allItems = $offers->each(function (Crawler $node, $i) {
			return $node->children();
		});

		// ID родительского товара
		$groupIds = $offers->each(function (Crawler $node, $i) {
			return $node->attr('group_id');
		});

		$offerIds = $offers->each(function (Crawler $node, $i) {
			return $node->attr('id');
		});

		// Получаем массив свойств для каждого оффера

		foreach ($allItems as $key => $item) {
			foreach ($item as $k => $v) {

				$ta[$key]["PARENT_ITEM_ID"] = $groupIds[$key];

				$ta[$key]["OFFER_ID"] = $offerIds[$key];

				if ($v->nodeName === 'name') {
					$ta[$key]['NAME'] = $v->nodeValue;
				}
				if ($v->nodeName === 'price') {
					$ta[$key]['PRICE'] = $v->nodeValue;
				}

				// Исключаем категории
				if ($v->nodeName === 'categoryId' && !in_array(trim((string)$v->nodeValue),
						[
							'374',
							'375',
							'376',
							'377',
							'378',
							'379',
							'380',
							'366',
							'357'
						]
					)
				) {
//					$catTempArray[] = $v->nodeValue;
					$ta[$key]['CATEGORY_ID'] = $v->nodeValue;

				}
				if ($v->nodeName === 'picture') {
					$ta[$key]['PICTURES'][] = $v->nodeValue;
				}
				if ($v->nodeName === 'description') {
					$ta[$key]['DESCRIPTION'] = $v->nodeValue;
				}
				$ta[$key]['ATTRIBUTES'] = $item->filter('param')->extract(['name', '_text']);
			}
		}

//		file_put_contents(__DIR__. "/logs/categories.log", print_r(array_unique($catTempArray), true));

		// Развернем полученный через extract массив атрибутов, извлечем размер
		foreach ($ta as $key => $value) {
			foreach ($value as $k => $v) {
				if ($k === "ATTRIBUTES") {
					foreach ($v as $i => $attribute) {
						$ta[$key][$k][$i] = array_flip($ta[$key][$k][$i]);
						if ($attribute[0] === "Размер") {
							$patterns = ['/"{1}/', '/<{1}/', '/>{1}/'];
							$replacement = ['\'\'', ' менее ', ' более '];
							$attribute[1] = preg_replace(
								$patterns,
								$replacement,
								trim(
									explode(
										":",
										preg_split(
											"/;\s+/",
											$attribute[1])[1])[1])
							);
						}
						$ta[$key][$k][$attribute[0]] = $attribute[1];
						unset($ta[$key][$k][$i]);
					}
				}
			}
		}

		// Получим массив уникальных ID родительских товаров
		foreach ($ta as $key => $value) {
			$parentItemsIdsArray[] = $value["PARENT_ITEM_ID"];
		}

		$parentItemsIdsArray = array_unique($parentItemsIdsArray);

		// Разобъем исходный массив по родительским товарам, исключая товары с ценой 0 и товары без категории
		foreach ($parentItemsIdsArray as $key => $id) {
			foreach ($ta as $k => $item) {
				if ($id === $item["PARENT_ITEM_ID"] && (int)$item["PRICE"] > 0 && !empty($item["CATEGORY_ID"])) {
					$groupedItemsArray[$id][] = $item;
				}
			}
		}

		return $groupedItemsArray;

	} catch (Exception $e) {
		return $e->getMessage();
	}
}

echo "Начат парсинг XML" . PHP_EOL;

$resultArray = parse();

echo "Парсинг завершен. Обновляем свойства элементов" . PHP_EOL;

//-------------------------------------------КОНЕЦ ПАРСЕРА------------------------------------------------------------//

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


// Транслитерация символьного кода

$translitParams = Array(
	"max_len" => "600", // обрезает символьный код до 100 символов
	"change_case" => "L", // буквы преобразуются к нижнему регистру
	"replace_space" => "_", // меняем пробелы на нижнее подчеркивание
	"replace_other" => "_", // меняем левые символы на нижнее подчеркивание
	"delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
	"use_google" => "false", // отключаем использование google
);


//---------------------------------------------ОБРАБОТКА РАЗМЕРОВ-----------------------------------------------------//


// Получаем массив уникальных значений размеров источника
$sourceSizesArray = [];

foreach ($resultArray as $key => $item) {
	foreach ($item as $k => $offer) {
		if (!empty($offer["ATTRIBUTES"]["Размер"])) {
			$sourceSizesArray[] = trim($offer["ATTRIBUTES"]["Размер"]);
		}
	}
}

$sourceSizesArray = array_unique($sourceSizesArray);

// Получаем массив существующих значений свойства "SIZE"
$sizePropArray = [];

$dbRes = CIBlockProperty::GetPropertyEnum(120,
	[], []
);

while ($res = $dbRes->GetNext()) {
	$sizePropArray[] = $res;
}

echo "Количество значений свойства 'SIZE' в базе: " . count($sizePropArray) . PHP_EOL;

$tmpSizeArray = [];
foreach ($sizePropArray as $key => $value) {
	$tmpSizeArray[] = $value["VALUE"];
}

$newSizesArray = null;

if (is_array($sizePropArray) && !empty($sizePropArray)) {
	$newSizesArray = array_values(array_diff($sourceSizesArray, $tmpSizeArray));
}

//----------------------------------Добавим новые значения в свойство "SIZE"------------------------------------------//

$tmpValueIdPairsArray = [];

foreach ($newSizesArray as $key => $sizeValue) {
	if (!in_array($sizeValue, $tmpSizeArray)) {
		$tmpValue = new CIBlockPropertyEnum;
		$tmpValue->Add(['PROPERTY_ID' => 120, 'VALUE' => $sizeValue]);
	}
}

// Заново получаем массив всех значений размеров

$sizePropArray = [];
$valueIdPairsArray = [];

$dbRes = CIBlockProperty::GetPropertyEnum(120,
	[], []
);

while ($res = $dbRes->GetNext()) {
	$sizePropArray[] = $res;
}

foreach ($sizePropArray as $key => $value) {
	$valueIdPairsArray[$value["VALUE"]] = $value["ID"];
}

//---------------------------------------КОНЕЦ ОБРАБОТКИ РАЗМЕРОВ-----------------------------------------------------//


//--------------------ПОЛУЧАЕМ СВОЙСТВА ТОРГОВЫХ ПРЕДЛОЖЕНИЙ----------------------------------------------------------//

$allSkuPropertiesArray = []; // Все свойства торговых предложений, уже существующие в инфоблоке ТП
$allSourcePropertiesArray = []; // Все свойства торговых предложений из прайса
$allSkuPropertiesCodesArray = []; // Массив символьных кодов ТП для проверки уникальности

$propsResDb = CIBlockProperty::GetList([], ["IBLOCK_ID" => SKU_IBLOCK_ID, "CHECK_PERMISSIONS" => "N"]);
while ($res = $propsResDb->GetNext()) {
	$allSkuPropertiesArray[] = $res;
}

foreach ($resultArray as $key => $item) {
	foreach ($item as $k => $offer) {
		foreach ($offer["ATTRIBUTES"] as $attribute => $attributeValue) {
			if (!in_array($attribute, $allSourcePropertiesArray)) {
				$allSourcePropertiesArray[] = $attribute;
			}
		}
	}
}

// Сохраним свойства в ИБ ТП, если их там еще нет

foreach ($allSkuPropertiesArray as $key => $property) {
	$allSkuPropertiesCodesArray[] = $property["CODE"];
}

foreach ($allSourcePropertiesArray as $key => $value) {

	$arPropertyFields = [
		"NAME" => $value,
		"ACTIVE" => "Y",
		"CODE" => strtoupper(CUtil::translit($value, "ru", $translitParams)),
		"PROPERTY_TYPE" => "S",
		"IBLOCK_ID" => SKU_IBLOCK_ID,
		"SEARCHABLE" => "Y",
		"FILTRABLE" => "Y",
		"VALUES" => [
			0 => [
				"VALUE" => "",
				"DEF" => "Y"
			]
		]
	];

	if (!in_array($arPropertyFields["CODE"], $allSkuPropertiesCodesArray)) {
		if ($arPropertyFields["CODE"] !== "BREND") {
			$newProperty = new CIBlockProperty;
			$newPropertyId = $newProperty->Add($arPropertyFields);

			if ($newPropertyId > 0) {
				echo "Свойство торговых предложений ID = {$newPropertyId} успешно добавлено \n";
			}
		} else {
			echo "Свойство с символьным кодом {$arPropertyFields['CODE']} уже существует или исключено из записи\n";
		}
	}
}

//---------------------------------ПРОИЗВОДИТЕЛЬ [справочник/highload]------------------------------------------------//


// Записываем всех производителей, которых там нет, в справочник Manufacturer

Loader::includeModule('highloadblock');

$manufacturerArray = [];

$hlManufacturer = HL\HighloadBlockTable::getById(HIGHLOAD_ID)->fetch();
$entity = HL\HighloadBlockTable::compileEntity($hlManufacturer);
$dataClass = $entity->getDataClass();
$manufacturerFields = $entity->getFields();

$tempData = $dataClass::getList([
	'select' => ['*']
]);

while ($res = $tempData->fetch()) {
	$manufacturerArray[] = $res;
}

// Массив возможных значений свойства 'Бренд' из источника
$sourceBrandsArray = [];
$manufacturerXmlIds = [];

foreach ($resultArray as $key => $item) {
	foreach ($item as $k => $offer) {
		if (!empty($offer["ATTRIBUTES"]["Бренд"])) {
			$sourceBrandsArray[] = trim($offer["ATTRIBUTES"]["Бренд"]);
		}
	}
}

$sourceBrandsArray = array_values(array_unique($sourceBrandsArray));

foreach ($manufacturerArray as $manId => $man) {
	$manufacturerXmlIds[] = $man["UF_XML_ID"];
}

// Цикл для записи брендов в HL

foreach ($sourceBrandsArray as $brandId => $brand) {
	if (!in_array(CUtil::translit($brand, 'ru', $translitParams), $manufacturerXmlIds)) {
		$result = $dataClass::add(
			[
				"UF_NAME" => strtoupper($sourceBrandsArray[$brandId]),
				"UF_XML_ID" => CUtil::translit($sourceBrandsArray[$brandId], 'ru', $translitParams),
				"UF_LINK" => "/brands/" . strtolower(CUtil::translit($sourceBrandsArray[$brandId], 'ru', $translitParams)) . "/",
			]
		);
		echo "В справочник добавлен новый производитель ID = " . $result->getId() . "\n";
	}
}

// Получаем значения из HL еще раз

unset($tempData);

$manufacturerArray = [];

$tempData = $dataClass::getList([
	'select' => ['*']
]);

while ($res = $tempData->fetch()) {
	$manufacturerArray[] = $res;
}

// Создаем массив пар ИМЯ=>XML_ID для использования при сохранении товара
$manValueIdPairsArray = [];

foreach ($manufacturerArray as $manId => $man) {
	$manValueIdPairsArray[$man["UF_NAME"]] = $man["UF_XML_ID"];
}

require (__DIR__ . "/add.php");

//--------------------------------------ОБНОВЛЕНИЕ (UPDATE) ЭЛЕМЕНТОВ-------------------------------------------------//
//--------------------------------------КОНЕЦ ОБНОВЛЕНИЯ (UPDATE) ЭЛЕМЕНТОВ-------------------------------------------//


register_shutdown_function(function () {
	global $counter;
	global $startExecTime;
	file_put_contents(__DIR__ . "/counter.log", $counter);
	$elapsedMemory = (!function_exists('memory_get_usage'))
		? '-'
		: round(memory_get_usage() / 1024 / 1024, 2) . ' MB';
	echo "\nВремя работы скрипта: " . (getmicrotime() - $startExecTime) . " сек\n";
	echo "Использованная память: " . $elapsedMemory . PHP_EOL;
});

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");