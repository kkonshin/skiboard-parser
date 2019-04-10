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

use Parser\Source\Source;
use Parser\Source\Storage;
use Parser\ParserBody\ParserBody;

use Parser\HtmlParser\HtmlParser;

use Parser\Update;
use Parser\CatalogDate;
use Parser\SectionsList;
use Parser\Mail;

use Parser\Utils\Price;

global $USER;

if (!is_dir(__DIR__ . "/logs")) {
	mkdir(__DIR__ . "/logs", 0777, true);
}
if (!is_dir(__DIR__ . "/save")) {
	mkdir(__DIR__ . "/save", 0777, true);
}

if (!Loader::includeModule('iblock')) {
	die('Не удалось загрузить модуль инфоблоки');
}

if (!Loader::includeModule('catalog')) {
	die('Невозможно загрузить модуль торгового каталога');
}

/**
 * Инициализация объекта для работы с источником
 */

$source = new Source(SOURCE);

//$sourceFile = Storage::storeCurrentXml($source); // Не вызывать до реализации сохранения временного файла?

if(is_file($sourceFile)) {
	echo $sourceFile . " успешно сохранен" . PHP_EOL; // Сохранение файла - источника
}

/**
 * Получение содержания файла - источника
 */

$xml = $source->getSource();

// TODO сохранить временный файл, получить его размер, удалить файл

/**
 * Получение предыдущего сохраненного файла - источника
 */

$previousXml = Storage::getPreviousXml();

$previousResultArray = [];

$resultDifferenceArray = [];
$resultDifferenceArrayKeys = [];

$isAddNewItems = false;

$resultArrayLength = 0;
$previousResultArrayLength = 0;

// TODO разделяем парсинг, запись свойств, запись элементов, апдейт свойств (?), апдейт элементов

$crawler = new Crawler($xml);

if (!empty($previousXml)) {
	$previousCrawler = new Crawler($previousXml);
}

// TODO удалить после тестирования

/*
$newSectionsList = [123,321,145];

$mailSendResult = Parser\Mail::sendMail($newSectionsList);

echo $mailSendResult->getId() . PHP_EOL; // ID записи в таблице b_event при удачном добавлении письма в очередь отправки
*/

// если даты каталогов не совпадают, значит получен новый прайс, распарсим его для получения даты

// TODO убрать дублирование парсинга нового файла

if ($crawler && $previousCrawler) {
	$isNewPrice = Parser\CatalogDate::checkDate($crawler, $previousCrawler);
}

if (!empty($previousXml) && $isNewPrice) {

	$previousResultArray = ParserBody::parse($previousCrawler);  // Парсим старый файл

	if (!empty($previousResultArray)) {
		$previousResultArrayLength = count($previousResultArray);
	}
}

$resultArray = ParserBody::parse($crawler); // Парсим новый файл в любом случае

//file_put_contents(__DIR__ . "/logs/resultArray.log", print_r($resultArray, true));
//file_put_contents(__DIR__ . "/logs/previousResultArray.log", print_r($previousResultArray, true));

//exit("Выход перед запуском HTML-парсера" . PHP_EOL);

//$resultArray = array_slice($resultArray, 23, 5); // Для отладки

// Детальное изображение, дополнительные фотографии, детальное описание из HTML-парсера

// TODO вынести в отдельный класс или метод класса HtmlParser?

foreach ($resultArray as $key => $value) {

	foreach ($value as $k => $v) {

		$body = HtmlParser::getBody($v["URL"]);

		if (!empty($body)) {

			$resultArray[$key][$k]["HTML_DETAIL_PICTURE_URL"] = HtmlParser::getDetailPicture($body);

            $resultArray[$key][$k]["HTML_MORE_PHOTO"] = HtmlParser::getMorePhoto($body);

            $resultArray[$key][$k]["HTML_DESCRIPTION"] = HtmlParser::getDescription($body);

            if (!empty($resultArray[$key][$k]["HTML_DESCRIPTION"])) {
				$resultArray[$key][$k]["HTML_PARSED_DESCRIPTION"] = HtmlParser::parseDescription($resultArray[$key][$k]["HTML_DESCRIPTION"]);
			}
		}
	}
}


//exit("Выход после окончания работы HTML-парсера");

$dbRes = CIBlockElement::GetList(
	[],
	[
		"IBLOCK_ID" => CATALOG_IBLOCK_ID,
		"SECTION_ID" => TEMP_CATALOG_SECTION
	],
	false,
	false, ["ID"]
);

while ($res = $dbRes->GetNext()) {
	$catalogIdsTempArray[] = $res;
}

foreach ($catalogIdsTempArray as $cidsKey => $cidsValue) {
	$catalogIds[] = $cidsValue["ID"];
}

// TODO - проверить свойство для каталога gssport

$catalogSkus = CCatalogSku::getOffersList(
	$catalogIds,
	CATALOG_IBLOCK_ID,
	[],
	["*"],
	[
		"CODE" => ["SKIBOARD_EXTERNAL_OFFER_ID"]
	]
);

//echo "Количество товаров во временном разделе: " . count($catalogSkus) . PHP_EOL;

foreach ($catalogSkus as $skuKey => $skuValue) {
	foreach ($skuValue as $key => $value) {
		$catalogSkusWithoutParent[] = $value;
		$skusPrices[] = CPrice::GetBasePrice($key);
	}
}

//echo "Количество торговых предложений: " . count($catalogSkusWithoutParent) . PHP_EOL;

foreach ($catalogSkusWithoutParent as $skuKey => $skuValue) {
	foreach ($skusPrices as $priceKey => $priceValue) {
		if ($skuValue["ID"] == $priceValue["PRODUCT_ID"]) {
			$catalogSkusWithoutParent[$skuKey]["PRICE"] = $priceValue["PRICE"];
		}
	}
}

/**
 * Обновление цен торговых предложений
 */

if (!empty($catalogSkusWithoutParent) && !empty($resultArray)) {
	Price::update($catalogSkusWithoutParent, $resultArray);
}

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
			["IBLOCK_ID" => CATALOG_IBLOCK_ID, "SECTION_ID" => TEMP_CATALOG_SECTION, "PROPERTY_GROUP_ID" => $resultDifferenceArrayKeys],
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

$dbRes = CIBlockProperty::GetPropertyEnum(SIZE_PROPERTY_ID,
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
		$tmpValue->Add(['PROPERTY_ID' => SIZE_PROPERTY_ID, 'VALUE' => $sizeValue]);
	}
}

// Заново получаем массив всех значений размеров

$sizePropArray = [];
$valueIdPairsArray = [];

$dbRes = CIBlockProperty::GetPropertyEnum(SIZE_PROPERTY_ID,
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
				if (!in_array($attribute, P_PROPERTIES_TO_EXCLUDE)) {
					$allSourcePropertiesArray[] = $attribute;
				}
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
		if (!empty($offer["BRAND"])) {
			$sourceBrandsArray[] = trim($offer["BRAND"]);
		}
	}
}

$sourceBrandsArray = array_values(array_unique($sourceBrandsArray));

//file_put_contents(__DIR__ . "/logs/sourceBrandsArray.log", print_r($sourceBrandsArray, true));

foreach ($manufacturerArray as $manId => $man) {
	$manufacturerXmlIds[] = $man["UF_XML_ID"];
}

//file_put_contents(__DIR__ . "/logs/manXmlIds.log", print_r($manufacturerXmlIds, true));

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

//file_put_contents(__DIR__ . "/logs/manValueIdPairsArray.log", print_r($manValueIdPairsArray, true));

// Сохранение товаров

// FIXME запуск add должен происходить по определенным условиям
//if($isAddNewItems){
echo "\nСохраняем товары" . PHP_EOL;
require(__DIR__ . "/add.php");
//}

echo "Новый каталог сохранен по адресу: " . Storage::storeCurrentXml($source) . PHP_EOL; // Сохранение файла - источника

register_shutdown_function(function () {
	global $startExecTime;
	$elapsedMemory = (!function_exists('memory_get_usage'))
		? '-'
		: round(memory_get_usage() / 1024 / 1024, 2) . ' MB';
	echo "\nВремя работы скрипта: " . number_format((getmicrotime() - $startExecTime), 2) . " сек\n";
	echo "Использованная память: " . $elapsedMemory . PHP_EOL;
});

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
