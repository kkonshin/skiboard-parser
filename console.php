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
use Parser\Update;
use Parser\CatalogDate;

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
$isAddNewItems = false;
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

// TODO DRY

$crawler = new Crawler($xml);

$previousCrawler = new Crawler($previousXml);

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

				if (in_array((int)$ta[$key]['CATEGORY_ID'], SUMMER)) {
					$ta[$key]["SEASON_PRICE"] = (string)round($ta[$key]["PRICE"] * 1.5, 2);
				}

				if (in_array((int)$ta[$key]['CATEGORY_ID'], WINTER)) {
					$ta[$key]["SEASON_PRICE"] = (string)round($ta[$key]["PRICE"] * 1.6, 2);
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

//TODO DRY


if (!empty($previousXml) && Parser\CatalogDate::checkDate($crawler, $previousCrawler)) {
	$previousResultArray = parse($previousXml);
	if (!empty($previousResultArray)) {
		$previousResultArrayLength = count($previousResultArray);
	}
}

$resultArray = parse($xml);

//file_put_contents(__DIR__ . "/resultArray.log", print_r($resultArray, true));

$dbRes = CIBlockElement::GetList([], ["IBLOCK_ID" => CATALOG_IBLOCK_ID, "ACTIVE" => "Y", "SECTION_ID" => 345], false, false, ["ID"]);

while ($res = $dbRes->GetNext()) {
	$catalogIdsTempArray[] = $res;
}

foreach ($catalogIdsTempArray as $cidsKey => $cidsValue) {
	$catalogIds[] = $cidsValue["ID"];
}

$catalogSkus = CCatalogSku::getOffersList($catalogIds, CATALOG_IBLOCK_ID, [], ["*"], ["CODE" => ["EXTERNAL_OFFER_ID"]]);

echo "Количество товаров в разделе skiboard_tmp: " . count($catalogSkus) . PHP_EOL;

foreach ($catalogSkus as $skuKey => $skuValue) {
	foreach ($skuValue as $key => $value) {
		$catalogSkusWithoutParent[] = $value;
		$skusPrices[] = CPrice::GetBasePrice($key);
	}
}

echo "Количество торговых предложений: " . count($catalogSkusWithoutParent) . PHP_EOL;

foreach ($catalogSkusWithoutParent as $skuKey => $skuValue) {
	foreach ($skusPrices as $priceKey => $priceValue) {
		if ($skuValue["ID"] == $priceValue["PRODUCT_ID"]) {
			$catalogSkusWithoutParent[$skuKey]["PRICE"] = $priceValue["PRICE"];
		}
	}
}

// Проверка изменения цен в новом каталоге

// FIXME update в данный момент не работает, несмотря на возвращаемый код удачного завершения

foreach ($catalogSkusWithoutParent as $offerIdKey => $offerIdValue) {
	foreach ($resultArray as $resultKey => $resultItem) {
		foreach ($resultItem as $offerKey => $offerValue) {
			if ($offerValue["OFFER_ID"] === $offerIdValue["PROPERTIES"]["EXTERNAL_OFFER_ID"]["VALUE"]) {
				echo $offerIdValue["PROPERTIES"]["EXTERNAL_OFFER_ID"]["VALUE"] . "  ";
				if ($offerValue["SEASON_PRICE"] !== $offerIdValue["PRICE"]) {
				    // Цена товара с уже произведенной наценкой из актуального прайса skiboard.ru
//					echo $offerValue["SEASON_PRICE"] . PHP_EOL;
					// Цена товара с наценкой из актуального прайса vs цена товара, записанная в инфоблоке в данный момент
					echo "Новая цена с наценкой " . $offerValue["SEASON_PRICE"] . " vs " . " цена в инфоблоке " . $offerIdValue["PRICE"] . PHP_EOL;
//					echo CPrice::Update(1, ["PRODUCT_ID" =>$offerIdValue["ID"], "PRICE" => $offerValue["SEASON_PRICE"], "CURRENCY" => "RUB"]) . PHP_EOL;
				}
			}
		}
	}
}


//file_put_contents("logs/catalog_ids.log", print_r($catalogIds, true));
//file_put_contents("logs/catalog_skus.log", print_r($catalogSkus, true));
//file_put_contents("logs/skusPrices.log", print_r($skusPrices, true));
//file_put_contents("logs/catalogSkusNoParent.log", print_r($catalogSkusWithoutParent, true));

if (!empty($resultArray)) {
	$resultArrayLength = count($resultArray);
}

echo "Длина массива обновлений: " . $resultArrayLength . PHP_EOL;
echo "Длина исходного массива: " . $previousResultArrayLength . PHP_EOL;

if ($previousResultArrayLength > 0 && $resultArrayLength !== $previousResultArrayLength) {

	$resultArrayKeys = array_keys($resultArray);
	$previousResultArrayKeys = array_keys($previousResultArray);

	// TODO берем массив с большей длиной для определения разницы
	if ($resultArrayLength > $previousResultArrayLength) {

		$resultDifferenceArrayKeys = array_diff($resultArrayKeys, $previousResultArrayKeys);
		foreach ($resultDifferenceArrayKeys as $diffKey => $diffValue) {
			$temp[$diffValue] = $resultArray[$diffValue];
		}
		$resultArray = $temp;
		// Значит нужно записать в инфоблок новые элементы с ключами разницы
		// т.е. выбрать из нового массива только эти элементы

		$isAddNewItems = true;

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
//	file_put_contents(__DIR__ . "/resultArray.log", print_r($resultArray, true));
//	file_put_contents(__DIR__ . "/diffResultArray.log", var_export($diffResultArray, true));
}

echo "Парсинг завершен. Обновляем свойства элементов" . PHP_EOL;

//-------------------------------------------КОНЕЦ ПАРСЕРА------------------------------------------------------------//

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

// Сохранение товаров

if ($isNewBasicSource || $isAddNewItems) {
	echo "\nСохраняем товары" . PHP_EOL;
	require(__DIR__ . "/add.php");
}

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