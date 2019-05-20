#!/usr/bin/php
<?php

if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки: php -f console.php');
}

require_once(__DIR__ . "/config.php");  // настройки и константы

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// Очищаем все 3 уровня буфера битрикса
while (ob_get_level()) {
	ob_end_flush();
}
// Засекаем время выполнения скрипта
$startExecTime = getmicrotime();
// Подключаем классы через composer
require_once("vendor/autoload.php");

use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock as HL;

use Parser\Source\Source;
use Parser\Source\Storage;
use Parser\ParserBody\ParserBody; // Основной парсер XML. Различается для разных сайтов-источников
use Parser\HtmlParser\HtmlParser; // Дополнительный парсер для получения картинок и детального описания из HTML

if (!Loader::includeModule('iblock')) {
	die('Не удалось загрузить модуль инфоблоки');
}
if (!Loader::includeModule('catalog')) {
	die('Невозможно загрузить модуль торгового каталога');
}

global $translitParams;

$source = \SOURCE;
$resultArray = []; // результат парсинга нового полученного XML-каталога с сайта-донора
$addArray = []; // массив товаров, которые будут добавлены в каталог
$catalogItemsExternalIds = []; // Внешние ключи товаров каталога
$newItems = []; // Массив новых товаров для отправки почтового уведомления менеджерам
$crawler = null; // объект компонента Symfony
$result = null; // Результат отправки почтового уведомления менеждерам

// Создаем директории для сохранения файлов каталогов, логирования и т.п.
Parser\Utils\Dirs::make(__DIR__);
// Получаем название сайта из опций главного модуля, т.к. контекст у нас - CLI
$serverName = \Bitrix\Main\Config\Option::get('main', 'server_name');
// Проверяем галку 'Установка для разработки'
$isDevServer = \Bitrix\Main\Config\Option::get('main', 'update_devsrv');

// Здесь можно переопределить параметры для тестового сайта, например ID временного раздела
if ($isDevServer === "Y") {
	echo "В главном модуле включена опция 'Установка для разработки'. Параметры config.php будут переопределены." . PHP_EOL;
	$serverName = "rocketstore.profi-server.ru";
	$source = "save/diller2.xml";
}
// Конфигурируем объект для работы с сохраненными элементами каталога
$sectionParams = new Parser\SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION, SKU_IBLOCK_ID);
// Создаем объект для работы с товарами временного раздела
$items = new Parser\Catalog\Items($sectionParams);
// Создаем экземпляр источника, фактически это путь к каталогу товаров на сайте-источнике
$source = new Source($source);
// Получаем содержание каталога с сайта-источника, которое и будем парсить
$xml = $source->getSource();
// Создаем краулер для нового каталога
$crawler = new Symfony\Component\DomCrawler\Crawler($xml);
// Парсим новый каталог
$resultArray = ParserBody::parse($crawler);

//file_put_contents(__DIR__ . "/logs/resultArray.log", print_r($resultArray, true));
// Создаем свойство для хранения внешнего ключа товара, если оно не существует
Parser\Catalog\Properties::createExternalItemIdProperty(
	[
		"NAME" => "Идентификатор товара в каталоге gssport.ru",
		"CODE" => "P_GSSPORT_GROUP_ID",
		"IBLOCK_ID" => CATALOG_IBLOCK_ID
	]
);

// Создаем свойство для хранения внешнего ключа торгового предложения, если оно не существует
Parser\Catalog\Properties::createExternalItemIdProperty(
	[
		"NAME" => "Идентификатор торгового предложения в каталоге gssport.ru",
		"CODE" => "P_GSSPORT_EXTERNAL_OFFER_ID",
		"IBLOCK_ID" => SKU_IBLOCK_ID
	]
);

// Массив товаров временного раздела
$catalogItems = $items->getList([], ["PROPERTY_P_GSSPORT_GROUP_ID"])->list;
// Очищает результаты предыдущей выборки
$items->reset();

foreach ($catalogItems as $item) {
	if (strlen($item["PROPERTY_P_GSSPORT_GROUP_ID_VALUE"]) > 0) {
		$catalogItemsExternalIds[] = $item["PROPERTY_P_GSSPORT_GROUP_ID_VALUE"];
	}
}

$i = 0;
foreach ($resultArray as $itemKey => $item) {
	foreach ($item as $offerKey => $offer) {
		$i++;
	}
}

$resultArrayKeys = array_keys($resultArray);
// Товары (внешние ключи), которые будут добавлены в каталог
$differenceAdd = array_values(array_diff($resultArrayKeys, $catalogItemsExternalIds));
$differenceAddCount = count($differenceAdd);
// Товары (внешние ключи), торговые предложения которых будут установлены в 0
$differenceDisable = array_values(array_diff($catalogItemsExternalIds, $resultArrayKeys));
$differenceDisableCount = count($differenceDisable);
// Товары (внешние ключи), торговые предложения которых будут установлены в 5. Все товары, кроме отключаемых.
$restoreQuantityItems = array_values(array_diff($catalogItemsExternalIds, $differenceDisable));
$restoreQuantityItemsCount = count($restoreQuantityItems);

//file_put_contents(__DIR__ . "/logs/console__resultArray.log", print_r($resultArray, true));
//file_put_contents(__DIR__ . "/logs/console__resultArrayKeys.log", print_r($resultArrayKeys, true));
//file_put_contents(__DIR__ . "/logs/console__differenceAdd.log", print_r($differenceAdd, true));
//file_put_contents(__DIR__ . "/logs/console__differenceAddCount.log", print_r($differenceAddCount, true));
//file_put_contents(__DIR__ . "/logs/console__differenceDisable.log", print_r($differenceDisable, true));
//file_put_contents(__DIR__ . "/logs/console__differenceDisable--keys.log", print_r(array_keys($differenceDisable), true));
//file_put_contents(__DIR__ . "/logs/console__catalogItemsExternalIds.log", print_r($catalogItemsExternalIds, true));
//file_put_contents(__DIR__ . "/logs/console__differenceDisableCount.log", print_r($differenceDisableCount, true));
//file_put_contents(__DIR__ . "/logs/console__restoreQuantityItems.log", print_r($restoreQuantityItems, true));

// Массив торговых предложений временного раздела
$catalogSkus = $items->getList()
	->getItemsIds()
	->getSkusList(["CODE" => ["P_GSSPORT_EXTERNAL_OFFER_ID"]])
	->getSkusListFlatten()
	->skusListFlatten;

$items->reset();

echo PHP_EOL;
echo "Количество товаров во временном разделе каталога: " . count($catalogItems) . PHP_EOL;
echo "Количество торговых предложений во временном разделе каталога: " . count($catalogSkus) . PHP_EOL;
echo "Количество товаров в новом файле XML: " . count($resultArrayKeys) . PHP_EOL;
echo "Количество торговых предложений в новом файле XML: " . $i . PHP_EOL;

unset($i);

if ($differenceDisableCount > 0 || $differenceAddCount > 0) {
	echo PHP_EOL;
	echo "Временный раздел будет обновлен" . PHP_EOL;
	echo PHP_EOL;
	if ($differenceDisableCount > 0) {
		echo "Товаров, количество ТП которых установлено в 0: " . $differenceDisableCount . PHP_EOL;
	}
	if ($differenceAddCount > 0) {
		echo "Товаров будет добавлено: " . $differenceAddCount . PHP_EOL;
		// Выбираем из $resultArray массив товаров для добавления
		foreach ($resultArray as $key => $item) {
			if (in_array($key, $differenceAdd)) {
				$addArray[$key] = $item;
			}
		}
		// Запускаем для выбранных товаров парсер HTML
		/*
		foreach ($addArray as $key => $value) {
			foreach ($value as $k => $v) {
				if (empty($v["URL"])){
					continue;
				}
				$body = HtmlParser::getBody($v["URL"]);
				if (!empty($body)) {
					$addArray[$key][$k]["HTML_DETAIL_PICTURE_URL"] = HtmlParser::getDetailPicture($body);
					$addArray[$key][$k]["HTML_MORE_PHOTO"] = HtmlParser::getMorePhoto($body);
					$addArray[$key][$k]["HTML_DESCRIPTION"] = HtmlParser::getDescription($body);
					if (!empty($addArray[$key][$k]["HTML_DESCRIPTION"])) {
						$addArray[$key][$k]["HTML_PARSED_DESCRIPTION"] = HtmlParser::parseDescription($addArray[$key][$k]["HTML_DESCRIPTION"]);
					}
				}
			}
		}
		*/

//		file_put_contents(__DIR__ . "/logs/addArray.log", print_r($addArray, true));
	}
} else {
	echo PHP_EOL;
	echo "Выгрузка и каталог совпадают, обновление не требуется" . PHP_EOL;
	echo PHP_EOL;
	return;
}

if ($differenceDisableCount > 0) {

	$filter = [
		"PROPERTY_P_GSSPORT_GROUP_ID" => $differenceDisable
	];

	$props = [
		"PROPERTY_P_GSSPORT_GROUP_ID"
	];

	$disableItemsList = $items->getList($filter, $props)->list;

	$items->reset();

	// TODO выбрать количество
	$disableSkusList = $items->getList($filter, $props)
		->getItemsIds()
		->getSkusList(["CODE" => ["P_GSSPORT_EXTERNAL_OFFER_ID"]])
		->getSkusListFlatten()
		->skusListFlatten;

	$items->reset();

//	echo PHP_EOL;

	foreach ($disableSkusList as $itemKey => $itemValue) {
		if ($itemValue["QUANTITY"] > 0) {
			CCatalogProduct::Update($itemKey, ["QUANTITY" => 0]);
			echo "Количество отсутствующего в новом прайсе ТП {$itemKey} - {$itemValue["NAME"]} установлено в 0" . PHP_EOL;
		}
	}
//	echo PHP_EOL;
}

// Восстанавливаем кол-во ТП в каталоге до 5
if ($restoreQuantityItemsCount > 0) {
	$filter = [
		"PROPERTY_P_GSSPORT_GROUP_ID" => $restoreQuantityItems
	];

	$props = [
		"PROPERTY_P_GSSPORT_GROUP_ID"
	];

	$restoreQuantitySkusList = $items->getList($filter, $props)
		->getItemsIds()
		->getSkusList(["CODE" => ["P_GSSPORT_EXTERNAL_OFFER_ID"]])
		->getSkusListFlatten()
		->skusListFlatten;

	$items->reset();

//	file_put_contents(__DIR__ . "/logs/console__restoreQuantitySkusList.log", print_r($restoreQuantitySkusList, true));

	foreach ($restoreQuantitySkusList as $itemKey => $itemValue) {
		if ($itemValue["QUANTITY"] < 5) {
			CCatalogProduct::Update($itemKey, ["QUANTITY" => 5]);
			echo "Количество ТП {$itemKey} - {$itemValue["NAME"]} восстановлено до 5 единиц" . PHP_EOL;
		}
	}
}
echo "Обновляем свойства товаров и торговых предложений" . PHP_EOL;

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

// Сохраняем товары во временный раздел
if ($differenceAddCount > 0) {
	echo PHP_EOL;
	echo "Сохраняем новые товары" . PHP_EOL;
	echo PHP_EOL;
	require(__DIR__ . "/add.php");
}

// Отправляем уведомление о новых товарах
$newItemsLength = count($newItems);
if (is_array($newItems) && $newItemsLength > 0) {
	try {
		$result = Parser\Mail::sendNewItems($newItems);
	} catch (Exception $e) {
		echo PHP_EOL . $e->getTraceAsString() . PHP_EOL;
	}
}

if ($result && $result->isSuccess()) {
	echo "Уведомление о {$newItemsLength} новых товарах успешно отправлено " . PHP_EOL;
}
require_once(__DIR__ . "/update_prices.php");
// Сохраняем текущий XML
echo Storage::storeCurrentXml($source);
// Завершаем скрипт и выводим статистику
register_shutdown_function(function () {
	global $startExecTime;
	$elapsedMemory = (!function_exists('memory_get_usage'))
		? '-'
		: round(memory_get_usage() / 1024 / 1024, 2) . ' MB';
	echo "\nВремя работы скрипта: " . number_format((getmicrotime() - $startExecTime), 2) . " сек\n";
	echo "Использованная память: " . $elapsedMemory . PHP_EOL;
});

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
