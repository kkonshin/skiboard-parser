#!/usr/bin/php
<?php

if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки: php -f console.php');
}

require(__DIR__ . "/config.php");  // настройки и константы
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// Очищаем все 3 уровня буфера битрикса
while (ob_get_level()) {
	ob_end_flush();
}
// Засекаем время выполнения скрипта
$startExecTime = getmicrotime();
// Подключаем классы через composer
require_once("vendor/autoload.php");

use Symfony\Component\DomCrawler\Crawler;

use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock as HL;

use Parser\SectionParams; // Класс для создания объектов параметров (DI)
use Parser\ItemsStatus; // Класс для работы с уже сохраненными в инфоблоки товаров и ТП элементами, принимает SectionParams

use Parser\Source\Source;
use Parser\Source\Storage;

use Parser\ParserBody\ParserBody;

use Parser\HtmlParser\HtmlParser;

use Parser\Update;

use Parser\Catalog\Properties; // Класс для работы со свойствами каталога
use Parser\Catalog\Items; // Класс для работы с товарами/ТП каталога

use Parser\CatalogDate;
use Parser\SectionsList;
use Parser\Mail;

use Parser\Utils\Price;
use Parser\Utils\Dirs;

global $USER;

if (!Loader::includeModule('iblock')) {
	die('Не удалось загрузить модуль инфоблоки');
}
if (!Loader::includeModule('catalog')) {
	die('Невозможно загрузить модуль торгового каталога');
}

$resultArray = []; // результат парсинга нового полученного XML-каталога с сайта-донора
$previousResultArray = []; // результат парсинга файла /save/previous.xml
$resultDifferenceArray = []; // массив разницы между результатами парсинга старого и нового каталога
$resultDifferenceArrayKeys = []; // его ключи - ID родительских товаров
$skusToSetZeroArray = []; // Массив ТП, подлежащих деактивации, если родительский товар отсутствует в новом каталоге

$catalogIdsTempArray = []; // временный рабочий массив
$temp = []; // временный рабочий массив

$isPriceNew = false; // true, если сохранен старый файл, получен новый каталог и даты в них не совпадают
$isAddNewItems = false; // флаг для запуска скрипта add.php [МЕХАНИЗМ НЕ РЕАЛИЗОВАН]

$resultArrayLength = 0; // длина нового массива
$previousResultArrayLength = 0; // длина старого массива

$pGroupId = ''; //


// TODO возможно инициализировать объекты через $crawler = new stdClass(),
// если реализована проверка на принадлежность к конкретному классу
$crawler = null; // объект компонента Symfony
$previousCrawler = null; // объект компонента Symfony

// Создаем директории для сохранения файлов каталогов, логирования и т.п.
Dirs::make(__DIR__);
// Создаем экземпляр источника, фактически это путь к каталогу товаров на сайте-источнике
$source = new Source(SOURCE);

// Конфигурируем объект для работы с сохраненными элементами каталога
$sectionParams = new SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION);
$catalogItems = new Items($sectionParams);

//TEMP
//$sourceFile = Storage::storeCurrentXml($source); // Не вызывать до реализации сохранения временного файла?
//if(is_file($sourceFile)) {
//	echo $sourceFile . " успешно сохранен" . PHP_EOL; // Сохранение файла - источника
//}
//ENDTEMP

// Получаем содержание каталога с сайта-источника, которое и будем парсить
$xml = $source->getSource();
// Проверяем, сохранен ли предыдущий файл каталога
$previousXml = Storage::getPreviousXml();
// Если старый файл есть - создаем ему краулер симфони...
if (!empty($previousXml)) {
	$previousCrawler = new Crawler($previousXml);
	// TODO можно переименовать старый файл на этом этапе?
	// TODO не сохранять старые файлы с датой
	//	Storage::rename(Storage::getSourceSavePath());
}
// Создаем краулер для нового каталога
$crawler = new Crawler($xml);

// Сразу парсим новый файл.
// TODO описание содержимого массива
$resultArray = ParserBody::parse($crawler);
// Детальное изображение, дополнительные фотографии, детальное описание забираем с сайта
// при помощи HTML-парсера
// TODO вынести в отдельный класс или метод класса HtmlParser?

//file_put_contents(__DIR__ . "/logs/resultArray__before.log", print_r($resultArray, true));

//TEMP
$resultArray = array_slice($resultArray, 1, 2); // Для отладки
//ENDTEMP

// TEMP включить после отладки
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
// ENDTEMP

//file_put_contents(__DIR__ . "/logs/resultArray__afterHTML.log", print_r($resultArray, true));

//exit("Выход после окончания работы HTML-парсера");

if ($crawler && $previousCrawler) {
	// Сравниваем даты в сохраненном файле и новом
	// TODO если есть старый файл - переименовать его перед сохранением нового
	$isPriceNew = CatalogDate::checkDate($crawler, $previousCrawler);
}

if (!empty($previousXml) && $isPriceNew) {
	// Парсим старый файл, не запуская для него HTML-парсер
	$previousResultArray = ParserBody::parse($previousCrawler);
	// Считаем длину получившегося массива
	$previousResultArrayLength = count($previousResultArray);
}

//file_put_contents(__DIR__ . "/logs/previousResultArray__before.log", print_r($previousResultArray, true));

//$resultArray = array_slice($resultArray, 23, 5); // Для отладки

// TODO получаем содержимое временного раздела каталога, куда мы сохраняем товары
// вынести в класс, получить $catalogIdsTempArray и $catalogIds
// найти их применения, выпилить лишнее

$dbRes = CIBlockElement::GetList(
	[],
	[
		"IBLOCK_ID" => CATALOG_IBLOCK_ID,
		"SECTION_ID" => TEMP_CATALOG_SECTION
	],
	false,
	false,
	[
		"ID"
	]
);

while ($res = $dbRes->GetNext()) {
	$catalogIdsTempArray[] = $res;
}

foreach ($catalogIdsTempArray as $cidsKey => $cidsValue) {
	$catalogIds[] = $cidsValue["ID"];
}



//file_put_contents(__DIR__ . "/logs/catalogIdsTempArray.log", print_r($catalogIdsTempArray, true));
//file_put_contents(__DIR__ . "/logs/catalogIds.log", print_r($catalogIds, true));

// ACHTUNG используется ли это свойство в других парсерах SKIBOARD_EXTERNAL_OFFER_ID
// зачем оно вообще?
// На D7 есть реализация?

// TODO Уже есть класс ItemsStatus с реализацией этих методов

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

// TODO что такое ТП без родителя?
// TODO здесь получаются базовые цены для ТП
// посмотреть на получаемые массивы
// перенести на D7
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

// Обновление цен торговых предложений
// TODO проверить, какие именно цены будут обновлены, все ?

if (!empty($catalogSkusWithoutParent) && !empty($resultArray)) {
	Price::update($catalogSkusWithoutParent, $resultArray); // Класс находится в classes/Utils/Price
}

if (!empty($resultArray)) {
	$resultArrayLength = count($resultArray);
}

echo "Длина массива обновлений: " . $resultArrayLength . PHP_EOL;
echo "Длина предыдущего массива обновлений: " . $previousResultArrayLength . PHP_EOL;

// Ищем разницу между новым и старым каталогом
if ($previousResultArrayLength > 0 && $resultArrayLength !== $previousResultArrayLength) {

	$resultArrayKeys = array_keys($resultArray);
	$previousResultArrayKeys = array_keys($previousResultArray);

	// Если новый массив длиннее старого
	if ($resultArrayLength > $previousResultArrayLength) {
		// Массив, содержащий ключи новых родительских товаров
		$resultDifferenceArrayKeys = array_diff($resultArrayKeys, $previousResultArrayKeys);

		// TODO убрать промежуточный массив?
		// Во временный массив выбираются товары вместе с дочерними ТП
		// Из них создается массив новых товаров для записи в инфоблок
		foreach ($resultDifferenceArrayKeys as $diffKey => $diffValue) {
			$temp[$diffValue] = $resultArray[$diffValue];
		}

		$resultArray = $temp;

		$isAddNewItems = true;

//		file_put_contents(__DIR__ . "/logs/resultArray__after--newLonger.log", print_r($resultArray, true));

		// Если новый массив короче старого
	} elseif ($previousResultArrayLength > $resultArrayLength) {
		// Получаем ключи родительских товаров, которые нужно убрать с сайта
		$resultDifferenceArrayKeys = array_diff($previousResultArrayKeys, $resultArrayKeys);
		// Проверяем наличие и, если свойства нет, создаем свойство каталога, хранящее ID товара в каталоге kite.ru
		Properties::createPGroupId(); // P_GROUP_ID

		$temp = $catalogItems->getList(
			["PROPERTY_P_GROUP_ID" => $resultDifferenceArrayKeys],
			["PROPERTY_P_GROUP_ID"]
		);

		// Деактивация заменена на установку количества всех ТП товара в 0
		// Получаем массив ТП по массиву родительских товаров
		foreach ($temp as $tempKey => $tempValue) {
			$skusToSetZeroArray = CCatalogSKU::getOffersList($tempValue["ID"]);
		}

		foreach ($skusToSetZeroArray as $itemKey => $itemValue) {
			echo PHP_EOL;
			echo "Товар {$itemKey} отсутствует в новом каталоге" . PHP_EOL;
			foreach ($itemValue as $offerKey => $offerValue) {
				CCatalogProduct::Update($offerKey, ["QUANTITY" => 0]);
				echo "Количество ТП {$offerKey} установлено в 0" . PHP_EOL;
			}
		}
		echo PHP_EOL;

//		file_put_contents(__DIR__ . "/logs/resultArray__after--newShorter--resultDifferenceArrayKeys.log", print_r($resultDifferenceArrayKeys, true));
//		file_put_contents(__DIR__ . "/logs/resultArray__after--skusToSetZero.log", print_r($skusToSetZeroArray, true));
	}

//	file_put_contents(__DIR__ . "/arrays_difference.log", print_r($resultDifferenceArrayKeys, true));
//	file_put_contents(__DIR__ . "/resultArrayKeys.log", var_export($resultArrayKeys, true));
//	file_put_contents(__DIR__ . "/previousResultArrayKeys.log", var_export($previousResultArrayKeys, true));
//	file_put_contents(__DIR__ . "/resultArray.log", print_r($resultArray, true));
//	file_put_contents(__DIR__ . "/diffResultArray.log", var_export($diffResultArray, true));
}

echo "Парсинг завершен. Обновляем свойства элементов" . PHP_EOL;

//exit("Выход после завершения парсинга");

//-------------------------------------------КОНЕЦ ПАРСЕРА------------------------------------------------------------//


// TEMP отправщик писем об обновлениях. Не реализован
/*
$newSectionsList = [123,321,145];

$mailSendResult = Parser\Mail::sendMail($newSectionsList);

echo $mailSendResult->getId() . PHP_EOL; // ID записи в таблице b_event при удачном добавлении письма в очередь отправки
*/
//ENDTEMP


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


// TODO +
// Ищем в свойствах инфоблока ТОВАРОВ свойство P_GROUP_ID
// Если нет - создаем. Это ключ связывающий родительские товары XML kite.ru и сохраненные товары
// Проверить необходимость для торговых предложений

// Выносим в класс +
/*
$catalogIbPropsDb = CIBlockProperty::GetList([], ["IBLOCK_ID" => CATALOG_IBLOCK_ID, "CHECK_PERMISSIONS" => "N", "CODE" => "P_GROUP_ID"]);

if($res=$catalogIbPropsDb->GetNext()){
    $pGroupId = $res;
}

if(empty($pGroupId)){
	$arPropertyFields = [
		"NAME" => "Идентификатор товара в каталоге kite.ru",
		"ACTIVE" => "Y",
		"CODE" => "P_GROUP_ID",
		"PROPERTY_TYPE" => "S",
		"IBLOCK_ID" => CATALOG_IBLOCK_ID,
		"SEARCHABLE" => "Y",
		"FILTRABLE" => "Y",
		"VALUES" => [
			0 => [
				"VALUE" => "",
				"DEF" => ""
			]
		]
	];

	$propertyPGroupId = new CIBlockProperty;
	$propertyPGroupId__id = $propertyPGroupId ->Add($arPropertyFields);

	if ($propertyPGroupId__id > 0) {
		echo "Добавлено свойство инфоблока товаров P_GROUP_ID" . PHP_EOL;
	}

}
*/
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
//echo "\nСохраняем товары" . PHP_EOL;
//require(__DIR__ . "/add.php");
//}

//TEMP
//echo "Новый каталог сохранен по адресу: " . Storage::storeCurrentXml($source) . PHP_EOL; // Сохранение файла - источника
//ENDTEMP

register_shutdown_function(function () {
	global $startExecTime;
	$elapsedMemory = (!function_exists('memory_get_usage'))
		? '-'
		: round(memory_get_usage() / 1024 / 1024, 2) . ' MB';
	echo "\nВремя работы скрипта: " . number_format((getmicrotime() - $startExecTime), 2) . " сек\n";
	echo "Использованная память: " . $elapsedMemory . PHP_EOL;
});

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
?>
