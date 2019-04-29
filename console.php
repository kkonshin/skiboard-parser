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

$skusToSetZeroArray = []; // Массив ТП, подлежащих установке в 0, если родительский товар отсутствует в новом каталоге

$skusPrices = []; // Массив цен торговых предложений, которые будут обновлены

$catalogIdsTempArray = []; // временный рабочий массив
$temp = []; // временный рабочий массив

$isPriceNew = false; // true, если сохранен старый файл, получен новый каталог и даты в них не совпадают
$isAddNewItems = false; // флаг для запуска скрипта add.php [МЕХАНИЗМ НЕ РЕАЛИЗОВАН]

$resultArrayLength = 0; // длина нового массива
$previousResultArrayLength = 0; // длина старого массива

$crawler = null; // объект компонента Symfony
$previousCrawler = null; // объект компонента Symfony

// Создаем директории для сохранения файлов каталогов, логирования и т.п.
Parser\Utils\Dirs::make(__DIR__);
// Конфигурируем объект для работы с сохраненными элементами каталога
$sectionParams = new Parser\SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION);
// Создаем объект для работы с товарами временного раздела
$items = new Parser\Catalog\Items($sectionParams);
// Создаем экземпляр источника, фактически это путь к каталогу товаров на сайте-источнике
$source = new Source(SOURCE);
// Получаем содержание каталога с сайта-источника, которое и будем парсить
$xml = $source->getSource();
// Проверяем, сохранен ли предыдущий файл каталога
$previousXml = Storage::getPreviousXml();
// Если старый файл есть - создаем ему краулер симфони...
if (!empty($previousXml)) {
	$previousCrawler = new Crawler($previousXml);
}
// Создаем краулер для нового каталога
$crawler = new Crawler($xml);
// Парсим новый каталог
$resultArray = ParserBody::parse($crawler);

//TEMP
//$resultArray = array_slice($resultArray, 30, 30, true); // Для отладки
//ENDTEMP

//file_put_contents(__DIR__ . "/logs/resultArray__before.log", print_r($resultArray, true));

if ($crawler && $previousCrawler) {
	$isPriceNew = Parser\CatalogDate::checkDate($crawler, $previousCrawler);
}

if (!empty($previousXml) && $isPriceNew) {
	// Парсим старый файл
	$previousResultArray = ParserBody::parse($previousCrawler);
	// Считаем длину получившегося массива
	$previousResultArrayLength = count($previousResultArray);
}

// Проверяем наличие и, если свойства нет, создаем свойство каталога для связи товара с XML
Parser\Catalog\Properties::createExternalItemIdProperty(
	[
		"NAME" => "Идентификатор товара в каталоге skiboard.ru",
		"CODE" => "P_SKIBOARD_GROUP_ID"
	]
);

$i = 0;

if (!empty($resultArray)) {
	$resultArrayLength = count($resultArray);
	foreach ($resultArray as $parentItem) {
		foreach ($parentItem as $offer) {
			$i++;
		}
	}
}

$params = [
	"IBLOCK_ID" => CATALOG_IBLOCK_ID,
	"SECTION_ID" => TEMP_CATALOG_SECTION
];

// Массив товаров временного раздела
$catalogItems = $items->getList($params, ["PROPERTY_P_SKIBOARD_GROUP_ID"])->list;
// Количество товаров во временном разделе DEPRECATED
$catalogItemsCount = count($catalogItems);
// Внешние ключи товаров каталога
$catalogItemsExternalIds = [];

foreach ($catalogItems as $item) {
	if (strlen($item["PROPERTY_P_SKIBOARD_GROUP_ID_VALUE"]) > 0) {
		$catalogItemsExternalIds[] = $item["PROPERTY_P_SKIBOARD_GROUP_ID_VALUE"];
	}
}

$resultArrayKeys = array_keys($resultArray);

$differenceAdd = array_diff($resultArrayKeys, $catalogItemsExternalIds);
$differenceDisable = array_diff($catalogItemsExternalIds, $resultArrayKeys);

// Массив торговых предложений временного массива
$catalogSkus = $items->getList($params)
	->getItemsIds()
	->getSkusList(["CODE" => ["SKIBOARD_EXTERNAL_OFFER_ID"]])
	->getSkusListFlatten()
	->skusListFlatten;

$catalogSkusCount = count($catalogSkus);

echo "Количество торговых предложений во временном разделе каталога: " . $catalogSkusCount . PHP_EOL;
echo "Количество товаров в новом файле XML: " . $resultArrayLength . PHP_EOL;
echo "Количество товаров в предыдущем файле XML  : " . $previousResultArrayLength . PHP_EOL;
if ($catalogSkusCount !== $i) {
	echo PHP_EOL . "Количество ТП во временном разделе и в XML не совпадают. Раздел будет обновлен." . PHP_EOL;
}

// TODO вместо использования предыдущего файла XML сравнивать с содержимым раздела каталога
// resultArray оставляем прежним, вместо previousResultArray делаем выборку из каталога
// сравниваем не длину, а ключи товаров

if ($previousResultArrayLength > 0 && $resultArrayLength !== $previousResultArrayLength) {

	$previousResultArrayKeys = array_keys($previousResultArray);

	// TODO берем массив с большей длиной для определения разницы
	if ($resultArrayLength > $previousResultArrayLength) {

		$resultDifferenceArrayKeys = array_diff($resultArrayKeys, $previousResultArrayKeys);
		foreach ($resultDifferenceArrayKeys as $diffKey => $diffValue) {
			$temp[$diffValue] = $resultArray[$diffValue];
		}
		$resultArray = $temp;
		// Добавляем новые товары в конце скрипта
		$isAddNewItems = true;

	} elseif ($previousResultArrayLength > $resultArrayLength) {

		// Если в новом XML отсуствуют товары из каталога - установим им кол-во ТП в 0
		$resultDifferenceArrayKeys = array_diff($previousResultArrayKeys, $resultArrayKeys);
		// TODO постоянно использующиеся названия свойств вынести в конфиг
		$catalogItemsList = $items->getList(
			["PROPERTY_P_SKIBOARD_GROUP_ID" => $resultDifferenceArrayKeys], // Фильтр
			["PROPERTY_P_SKIBOARD_GROUP_ID"] // Дополнительные свойства, которые нужно получить
		);

		foreach ($catalogItemsList->list as $key => $item) {
			$skusToSetZeroArray = CCatalogSKU::getOffersList($item["ID"]);
		}

		foreach ($skusToSetZeroArray as $itemKey => $itemValue) {
			echo PHP_EOL;

			// FIXME название не вывелось в консоль

			echo "Товар {$itemKey} - {$itemValue["NAME"]} отсутствует в новом файле XML" . PHP_EOL;
			foreach ($itemValue as $offerKey => $offerValue) {
				CCatalogProduct::Update($offerKey, ["QUANTITY" => 0]);
				echo "Количество отсутствующего в новом прайсе торгового предложения {$offerKey} установлено в 0" . PHP_EOL;
			}
		}
		echo PHP_EOL;
	}
}


//	file_put_contents(__DIR__ . "/logs/console__resultDifferenceArrayKeys.log", print_r($resultDifferenceArrayKeys, true));
//file_put_contents(__DIR__ . "/logs/console__catalogItems.log", print_r($catalogItems, true));
//file_put_contents(__DIR__ . "/logs/console__resultArray.log", print_r($resultArray, true));
file_put_contents(__DIR__ . "/logs/console__add.log", print_r($differenceAdd, true));
file_put_contents(__DIR__ . "/logs/console__disable.log", print_r($differenceDisable, true));
//file_put_contents(__DIR__ . "/logs/console__catalogSkus.log", print_r($catalogSkus, true));
//file_put_contents(__DIR__ . "/logs/console__externalIdsArray.log", print_r($externalIdsArray, true));
//file_put_contents(__DIR__ . "/logs/console__externalIdsDiff.log", print_r($externalIdsDiffArray, true));

echo "Парсинг завершен. Обновляем свойства элементов" . PHP_EOL;

exit();

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

// FIXME запуск add должен происходить по определенным условиям
if ($isAddNewItems) {
//	echo "\nСохраняем товары" . PHP_EOL;
	require(__DIR__ . "/add.php");
}
require_once(__DIR__ . "/update_prices.php");

// Сохраняем текущий XML
echo Storage::storeCurrentXml($source);

register_shutdown_function(function () {
	global $startExecTime;
	$elapsedMemory = (!function_exists('memory_get_usage'))
		? '-'
		: round(memory_get_usage() / 1024 / 1024, 2) . ' MB';
	echo "\nВремя работы скрипта: " . (getmicrotime() - $startExecTime) . " сек\n";
	echo "Использованная память: " . $elapsedMemory . PHP_EOL;
});

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");