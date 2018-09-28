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

require (__DIR__ . "/setsize.php");
require(__DIR__. "/setproperties.php");
require(__DIR__ . "/add.php");


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
$previousXml = null;
$previousResultArray = [];
$resultDifferenceArray = [];
$resultDifferenceArrayKeys = [];
$isNewBasicSource = false;
$resultArrayLength = 0;
$previousResultArrayLength = 0;

if (!is_file(SOURCE_SAVE_PATH . $previousSourceName)) {
	echo "Сохраняем каталог во временный файл" . PHP_EOL;
	file_put_contents(SOURCE_SAVE_PATH . $previousSourceName, $xml);
	// Если источник парсится впервые, запишем все товары во временный инфоблок (пока нет привязки к разделам)
	$isNewBasicSource = true;

} else {
	$previousXml = file_get_contents(SOURCE_SAVE_PATH . $previousSourceName);
}

// TODO разделяем парсинг, запись свойств, запись элементов, апдейт свойств (?), апдейт элементов

if (!function_exists("checkCatalogDate")) {
	function checkCatalogDate($xml, $previousXml)
	{
		$crawler = new Crawler($xml);
		$previousCrawler = new Crawler($previousXml);

		$sourceDate = $crawler->filter('yml_catalog')->attr('date');
		$previousSourceDate = $previousCrawler->filter('yml_catalog')->attr('date');

		if ($sourceDate === $previousSourceDate) {
			echo "Обновление каталога не требуется" . PHP_EOL;
			die();
		} else if (!empty($sourceDate)) {
			echo "Будет произведено обновление товаров каталога" . PHP_EOL;
			return true;
		}
		return false;
	}
}

function parse($xml)
{
	$ta = [];

	$crawler = new Crawler($xml);

	$sourceDate = $crawler->filter('yml_catalog')->attr('date');

	echo "Разбираем каталог от " . $sourceDate . PHP_EOL;

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

//-----------------------------------------function parse($xml) КОНЕЦ-------------------------------------------------//



if (!empty($previousXml) && checkCatalogDate($xml, $previousXml)) {
	$previousResultArray = parse($previousXml);
	if (!empty($previousResultArray)) {
		$previousResultArrayLength = count($previousResultArray);
	}
}

$resultArray = parse($xml);

if (!empty($resultArray)) {
	$resultArrayLength = count($resultArray);
}

echo "Длина массива обновлений: " . $resultArrayLength . PHP_EOL;
echo "Длина исходного массива: " . $previousResultArrayLength . PHP_EOL;

if ($previousResultArrayLength > 0 && $resultArrayLength !== $previousResultArrayLength) {

	$resultArrayKeys = array_keys($resultArray);
	$previousResultArrayKeys = array_keys($previousResultArray);

	if ($resultArrayLength > $previousResultArrayLength) {

		$resultDifferenceArrayKeys = array_diff($resultArrayKeys, $previousResultArrayKeys);
		foreach ($resultDifferenceArrayKeys as $diffKey => $diffValue) {
			$temp[$diffValue] = $resultArray[$diffValue];
		}
		$resultArray = $temp;
		// Значит нужно записать в инфоблок новые элементы с ключами разницы
		// т.е. выбрать из нового массива только эти элементы

        // TODO создать свойства для новых товаров
		$valueIdPairsArray = setSize($resultArray);

		file_put_contents("tmp.php", print_r($valueIdPairsArray, true));


		setProperties($resultArray);
        addItems($resultArray);

	} elseif ($previousResultArrayLength > $resultArrayLength) {

		$resultDifferenceArrayKeys = array_diff($previousResultArrayKeys, $resultArrayKeys);

		// Значит в инфоблоке нужно деактивировать товары с ключами разницы

		$dbRes = CIBlockElement::GetList(
			[],
			["IBLOCK_ID" => CATALOG_IBLOCK_ID, "SECTION_ID" => 345, "PROPERTY_GROUP_ID" => $resultDifferenceArrayKeys],
			false,
			false,
			["IBLOCK_ID", "ID", "NAME", "PROPERTY_GROUP_ID", "ACTIVE"]
		);

		while ($res = $dbRes->GetNext()) {
			$temp[] = $res;
		}

		foreach ($temp as $tempKey => $tempValue) {
			$element = new CIBlockElement();
			$element->Update($tempValue["ID"], ["ACTIVE" => "N"]);
		}
	}


//	file_put_contents(__DIR__ . "/arrays_difference.log", print_r($resultDifferenceArrayKeys, true));
//	file_put_contents(__DIR__ . "/resultArrayKeys.log", var_export($resultArrayKeys, true));
//	file_put_contents(__DIR__ . "/previousResultArrayKeys.log", var_export($previousResultArrayKeys, true));
	file_put_contents(__DIR__ . "/temp.log", print_r($temp, true));
//	file_put_contents(__DIR__ . "/diffResultArray.log", var_export($diffResultArray, true));
}

echo "Парсинг завершен. Обновляем свойства элементов" . PHP_EOL;

//-------------------------------------------КОНЕЦ ПАРСЕРА------------------------------------------------------------//


//setSize($resultArray);


//--------------------ПОЛУЧАЕМ СВОЙСТВА ТОРГОВЫХ ПРЕДЛОЖЕНИЙ----------------------------------------------------------//
//setProperties($resultArray);
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

if ($isNewBasicSource) {
	echo "\nСохраняем товары" . PHP_EOL;
	$valueIdPairsArray = setSize($resultArray);
    setProperties($resultArray);
	addItems($resultArray);

}

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