<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?
ini_set('max_execution_time', '600');
set_time_limit(0);
//ini_set('display_errors', 1);
//ini_set('error_reporting', E_ALL);

require_once("vendor/autoload.php");

use Symfony\Component\DomCrawler\Crawler;
use \Bitrix\Main\Loader;

define('SOURCE', "http://b2b.skiboard.ru/yml_get/uzvev7kr159d");
define('SAVE_FILE', __DIR__ . "/save/parser_dump.php");
define('SKU_IBLOCK_ID', 13);
global $USER;

// TODO разнести офферы по категориям в зависимости от ID
// TODO - картинки сразу скачивать, а не ссылаться на сторонний сайт. CFile::MakeFileArray;
// Удалить лишнее в конце

// TODO буферизация, сохранение на диск, скачивание по частям?

// TODO СКИДКИ!!!!!!!!!!!!!! Проставлять скидки парсером или по свойству зима/лето битриксом?

//-------------------------------------------------ПАРСЕР-------------------------------------------------------------//


function parse()
{

	$ta = [];

	$xml = file_get_contents(SOURCE);

	$crawler = new Crawler($xml);
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

				//		$ta[$key][] = $v;

				$ta[$key]["PARENT_ITEM_ID"] = $groupIds[$key];

				$ta[$key]["OFFER_ID"] = $offerIds[$key];

				if ($v->nodeName === 'name') {
					$ta[$key]['NAME'] = $v->nodeValue;
				}
				if ($v->nodeName === 'price') {
					$ta[$key]['PRICE'] = $v->nodeValue;
				}

				// Исключаем категории
				if ($v->nodeName === 'categoryId' && !in_array($v->nodeValue,
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

		// Разобъем исходный массив по родительским товарам, исключает товары с ценой 0
		foreach ($parentItemsIdsArray as $key => $id) {
			foreach ($ta as $k => $item) {
				if ($id === $item["PARENT_ITEM_ID"] && (int)$item["PRICE"] > 0) {
					$groupedItemsArray[$id][] = $item;
				}
			}
		}

//		echo "<pre>";
//		print_r($groupedItemsArray);
//		echo "</pre>";

		// Сохраняем результаты парсинга, чтобы не парсить по несколько раз (DEVELOPMENT), в продакшене не использовать
		if (count($groupedItemsArray) > 0) {
			file_put_contents(SAVE_FILE, serialize($groupedItemsArray));
		}

		return $groupedItemsArray;

	} catch (Exception $e) {
		return $e->getMessage();
	}
}

// TODO проверку удалить в продакшене, если не будет реализовано кеширование.
// Если существует файл сохранения - парсер не запускается!

if (!is_file(SAVE_FILE)) {
	$resultArray = parse();
} else {
	echo "Данные извлечены из файла сохранения: <br>";
	$resultArray = unserialize(file_get_contents(SAVE_FILE));
}

// TMP - удалить
//$translitParams = Array(
//	"max_len" => "600", // обрезает символьный код до 100 символов
//	"change_case" => "L", // буквы преобразуются к нижнему регистру
//	"replace_space" => "_", // меняем пробелы на нижнее подчеркивание
//	"replace_other" => "_", // меняем левые символы на нижнее подчеркивание
//	"delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
//	"use_google" => "false", // отключаем использование google
//);
//
//foreach (array_slice($resultArray, 180, 10) as $key => $value){
//	$unique[] = CUtil::translit($value[0]["NAME"] .  $value[0]["OFFER_ID"], 'ru', $translitParams) . "<br>";
//	$unique[] = CUtil::translit($value[0]["NAME"] .  $value[0]["ATTRIBUTES"]['Артикул'] . $value[0]["ATTRIBUTES"]['Размер'], 'ru', $translitParams) . "<br>";
//	echo CUtil::translit($value[0]["NAME"] . ' ' . $value[0]["ATTRIBUTES"]['Артикул'] . ' ' .$value[0]["ATTRIBUTES"]['Размер'], 'ru', $translitParams) . "<br>";
//	echo $value[0]["NAME"] . ' ' .  $value[0]["OFFER_ID"] . "<br>";
//}
//echo count($unique) . "<br>";
//echo count(array_unique($unique));

//-------------------------------------------КОНЕЦ ПАРСЕРА------------------------------------------------------------//

$summer = array_unique([
	288,289,290,418,321,322,296,398,400,411,412,413,414,415,275,330,328,329,331,332,333,
	334,335,336,396,294,358,360,359,361,362,389,292,401,385,393,381,370,278,279,282,283,
	368,409,372,347,368,409,372,347,348,349,327,350,351,352,353,354,355,356,357,271,419,
	297,298,299,300,301,302,303,325,326,402,403,404,405,406,371,270,310,304,305,306,307,
	386,387,410,395,420,421,422,423,424,425,383,392,293,427
]);


$winter = array_unique([
	337,338,339,340,341,342,343,407,385,399,416,417,273,280,286,287,408,369,365,266,365,
	373,280,286,287,408,369,267,268,269,364,391
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

//echo "<pre>";
//print_r($sourceSizesArray);
//echo "</pre>";

// TODO --------------------------------преобразовать этот массив в полный?


//file_put_contents(__DIR__. "/save/escaped_source_sizes.php", print_r($sourceSizesArray, true));

// Получаем массив существующих значений свойства "SIZE"
$sizePropArray = [];

$dbRes = CIBlockProperty::GetPropertyEnum(120,
	[], []
);

while ($res = $dbRes->GetNext()) {
	$sizePropArray[] = $res;
}

echo "<pre>";
echo "Количество значений свойства 'SIZE' в базе: " . count($sizePropArray) . "<br>";
//print_r($sizePropArray);
echo "</pre>";


// Получим массив ID значений для последующего удаления именно размеров

$tmpSizeArray = [];
$deleteIdArray = [];

foreach ($sizePropArray as $key => $value) {
	$tmpSizeArray[] = $value["VALUE"];
	$deleteIdArray[] = $value["ID"];
}


//echo "<pre>";
//echo min($tmpIdArray) . ' - ' . max($tmpIdArray) ;
//print_r($tmpIdArray);
//echo "</pre>";


// Если массив свойств в базе пуст - загрузим дамп с рабочего

if (count($sizePropArray) === 0) {

	$productionSizesArray = null;

	$productionSizesArray = unserialize(file_get_contents(__DIR__ . "/save/size_dump.php"));

	foreach ($productionSizesArray as $key => $sizeValue) {
		CIBlockPropertyEnum::Add(
			[
				'PROPERTY_ID' => 120,
				'ID' => $sizeValue["ID"],
				'VALUE' => $sizeValue["VALUE"],
				'DEF' => $sizeValue["DEF"],
				'SORT' => $sizeValue["SORT"],
			]
		);
	}
}


//echo "<pre>";
//print_r($productionSizesArray);
//print_r($sourceSizesArray);
//echo "</pre>";

$newSizesArray = null;

if (is_array($sizePropArray) && !empty($sizePropArray)) {
	$newSizesArray = array_values(array_diff($sourceSizesArray, $tmpSizeArray));
}

//echo "<pre>";
//echo count($newSizesArray) + count($sizePropArray) . "<br>";
//echo "Разница: " . count($newSizesArray) . "<br>";
//print_r($newSizesArray);
//echo "</pre>";

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


// Удалим лишние ------------------------------------------------------------------------------------------------------

// При удалении и перезаписи значений свойств БУДЕТ ИЗМЕНЕН ID значения свойства
// ACHTUNG! Не пользоваться в лоб массовым удалением, посколько оно удаляет значения свойств ДЛЯ ВСЕХ свойств типа "список"

//if (!empty($deleteIdArray)) {
//	for ($i = min($deleteIdArray); $i < max($deleteIdArray) + 1; $i++) {
//		$del = new CIBlockPropertyEnum;
//		$r = $del->Delete($i);
//	}
//}

//echo "<pre>";
//print_r($sizePropArray);
//print_r($valueIdPairsArray);
//echo "</pre>";


//---------------------------------------КОНЕЦ ОБРАБОТКИ РАЗМЕРОВ-----------------------------------------------------//


//--------------------ПОЛУЧАЕМ СВОЙСТВА ТОРГОВЫХ ПРЕДЛОЖЕНИЙ----------------------------------------------------------//

// NOTICE можно фильтровать выборку по типу свойства
// NOTICE все новые свойства записывать как тип СТРОКА

$allSkuPropertiesArray = []; // Все свойства торговых предложений, уже существующие в инфоблоке ТП
$allSourcePropertiesArray = []; // Все свойства тогровых предложений из прайса
$allSkuPropertiesCodesArray = []; // Массив символьных кодов ТП для проверки уникальности

$propsResDb = CIBlockProperty::GetList([], ["IBLOCK_ID" => SKU_IBLOCK_ID, "CHECK_PERMISSIONS" => "N"]);
while ($res = $propsResDb->GetNext()) {
	$allSkuPropertiesArray[] = $res;
}

//echo "<pre>";
//echo "Все свойства инфоблока торговых предложений: <br>";
//print_r($allSkuPropertiesArray);
//echo "</pre>";

$resultArray = array_slice($resultArray, 0, 10, true);

foreach ($resultArray as $key => $item) {
	foreach ($item as $k => $offer) {
//		echo "<pre>";
//		echo "Выборка из 10 ТП для отладки, свойства ТП: <br>";
//		print_r($offer["ATTRIBUTES"]);
//		echo "</pre>";
		foreach ($offer["ATTRIBUTES"] as $attribute => $attributeValue) {
			if (!in_array($attribute, $allSourcePropertiesArray)) {
				$allSourcePropertiesArray[] = $attribute;
			}
		}
	}
}


//echo "<pre>";
//echo "Все уникальные свойства офферов источника: <br>";
//print_r($allSourcePropertiesArray);
//echo "</pre>";


//[0] => Артикул - строка
//[1] => Бренд - строка или список
//[2] => Коллекция - строка или число
//[3] => Пол - М/Ж/Не указан
//[4] => Тип - не записываем или строка "Дополнительная информация"
//[5] => Товар - не записываем, это категория?
//[6] => Размер - уже обработан
//[7] => Состав - строка
//[8] => Застежка - строка
//[9] => Стиль - строка
//[10] => Количество мест - строка
//[11] => Крой - строка
//[12] => Количество секций - строка
//[13] => Уровень - строка
//[14] => Шнуровка - строка
//[15] => Подошва - строка
//[16] => Мембрана - строка
//[17] => Толщина - строка
//[18] => Прогиб - строка
//[19] => Жесткость - строка
//[20] => Линза - строка

foreach ($resultArray as $key => $item){
	foreach($item as $k => $offer) {
		echo "<pre>";
//		print_r($offer["ATTRIBUTES"]);
		print_r($offer);
		echo "</pre>";
	}
}


// Сохраним эти свойства в ИБ ТП, если их там еще нет

// TODO проверить наличие свойства по названию ИЛИ СИМВОЛЬНОМУ КОДУ


foreach ($allSkuPropertiesArray as $key => $property){
	$allSkuPropertiesCodesArray[] = $property["CODE"];
}


//echo "<pre>";
//echo "Массив имен свойств ТП: <br>";
//print_r($allSkuPropertiesCodesArray);
//echo "</pre>";

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

		$newProperty = new CIBlockProperty;
		$newPropertyId = $newProperty->Add($arPropertyFields);

		if ($newPropertyId > 0) {
			echo "Свойство торговых предложений ID = {$newPropertyId} успешно добавлено <br>";
		}
	} else {
		echo "Свойство с таким символьным кодом уже существует <br>";
	}
}




//-----------------------------------------СОХРАНЕНИЕ (ADD) ЭЛЕМЕНТОВ (ПРОТОТИП)--------------------------------------//

// TODO try-catch на запись элемента

//$firstElement = array_slice($resultArray, 0, 1, true);
$resultArray = array_slice($resultArray, 0, 10, true);

foreach ($resultArray as $key => $item) {


	if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
		die('Невозможно загрузить модуль инфоблоков или торгового каталога');
	}

	$morePhotoArray = []; // Массив дополнительных картинок

	$arCatalog = CCatalog::GetByID(SKU_IBLOCK_ID);

// ID инфоблока товаров
	$IBlockCatalogId = $arCatalog['PRODUCT_IBLOCK_ID'];

//ID свойства в инфоблоке предложений типа "Привязка к товарам (SKU)"
	$SKUPropertyId = $arCatalog['SKU_PROPERTY_ID'];

	$obElement = new CIBlockElement;


// TODO Сохранять массив дополнительных картинок в соответствующем свойстве

// TODO Сохранять базовую цену, либо сразу цены со скидкой

// TODO Имя товара должно быть образовано из ТП, либо собирать по другому?

// TODO разграничить случаи ADD и UPDATE

// TODO Размер нужно вырезать в парсере из свойства Размер, поскольку там еще артикул и сезон, РАЗМЕР - свойство типа список!
// Бренд - свойство товара типа список

// Получаем значения размеров

	$itemFieldsArray = [
		"MODIFIED_BY" => $USER->GetID(),
		"IBLOCK_ID" => $IBlockCatalogId,
		"IBLOCK_SECTION_ID" => 345,
		"NAME" => $item[0]["NAME"],
		"CODE" => CUtil::translit($item[0]["NAME"] . ' ' . $item[0]["OFFER_ID"], "ru", $translitParams),
		"ACTIVE" => "Y",
		"DETAIL_PICTURE" => CFile::MakeFileArray($item[0]["PICTURES"][0]),
	];

	if ($productId = $obElement->Add($itemFieldsArray)) {
		echo "Добавлен товар " . $productId . "<br>";
	} else {
		echo "Ошибка: " . $obElement->LAST_ERROR . "<br>";
	}


	if ($productId) {

		foreach ($item as $k => $offer) {
//			echo "<pre>";
//		print_r($offer['ATTRIBUTES']['Размер']);
//		print_r($valueIdPairsArray[$offer['ATTRIBUTES']['Размер']]);
//		print_r($valueIdPairsArray);
//			echo "</pre>";

			$obElement = new CIBlockElement();

			// Цена торгового предложения

			$offerPrice = $offer["PRICE"];

			$arOfferProps = [
				$SKUPropertyId => $productId,
				'SIZE' => $valueIdPairsArray[$offer['ATTRIBUTES']['Размер']],
				'EXTERNAL_OFFER_ID' => $offer['OFFER_ID']
			];


			foreach ($offer['ATTRIBUTES'] as $propertyName => $propertyValue){
				$arOfferProps[strtoupper(CUtil::translit($propertyName, 'ru', $translitParams))] = $propertyValue;
			}

//			echo "<pre>";
//			echo "Значения свойств ТП для записи <br>";
//			print_r($arOfferProps);
//			echo "</pre>";

			// Дополнительные фотографии, раскомментировать если потребуются

//			foreach ($offer["PICTURES"] as $key => $picture){
//				$morePhotoArray[] = CFile::MakeFileArray($picture);
//			}


			// TODO проверить отображение детального описания, т.к. приходит htmlescape

			$arOfferFields = [
				'NAME' => $offer["NAME"] . " " . $offer["ATTRIBUTES"]["Размер"] . " " . $offer["ATTRIBUTES"]["Артикул"],
				'IBLOCK_ID' => SKU_IBLOCK_ID,
				'ACTIVE' => 'Y',
				"DETAIL_TEXT" => $offer["DESCRIPTION"],
				"DETAIL_PICTURE" => CFile::MakeFileArray($offer["PICTURES"][0]),
				'PROPERTY_VALUES' => $arOfferProps
			];

			// Получаем ID торгового предложения
			$offerId = $obElement->Add($arOfferFields);

			if ($offerId) {
				// Добавляем как товар и установим цену -----------------------НДС входит в цену   ??
				$catalogProductAddResult = CCatalogProduct::Add([
					"ID" => $offerId,
					'QUANTITY' => '5',
					"VAT_INCLUDED" => "Y"
				]);


				if (!$catalogProductAddResult) {
					throw new Exception("Ошибка добавление полей торгового предложения \"{$offerId}\"");
				}

				if ($catalogProductAddResult && !CPrice::SetBasePrice($offerId, $offerPrice, "RUB")) {
					throw new Exception("Ошибка установки цены торгового предложения \"{$offerId}\"");
				}
			} else {
				throw new Exception("Ошибка добавления торгового предложения: " . $obElement->LAST_ERROR);
			}
		}
	} else {
		throw new Exception("Ошибка добавления товара: " . $obElement->LAST_ERROR);
	}
}


//--------------------------------------КОНЕЦ СОХРАНЕНИЯ (ADD) ЭЛЕМЕНТОВ (ПРОТОТИП)-----------------------------------//


/*
//-----------------------------------------СОХРАНЕНИЕ (ADD) ЭЛЕМЕНТОВ (10 ЭЛЕМЕНТОВ)----------------------------------//
$firstTenElements = array_slice($resultArray, 0, 10, true);

//echo "<pre>";
//print_r($firstTenElements);
//echo "</pre>";

if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
	die('Невозможно загрузить модуль инфоблоков или торгового каталога');
}


$arCatalog = CCatalog::GetByID(SKU_IBLOCK_ID);

// ID инфоблока товаров
$IBlockCatalogId = $arCatalog['PRODUCT_IBLOCK_ID'];

//ID свойства в инфоблоке предложений типа "Привязка к товарам (SKU)"
$SKUPropertyId = $arCatalog['SKU_PROPERTY_ID'];

// Транслитерация символьного кода

$translitParams = Array(
	"max_len" => "600", // обрезает символьный код до 100 символов
	"change_case" => "L", // буквы преобразуются к нижнему регистру
	"replace_space" => "_", // меняем пробелы на нижнее подчеркивание
	"replace_other" => "_", // меняем левые символы на нижнее подчеркивание
	"delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
	"use_google" => "false", // отключаем использование google
);

// TODO Сохранять массив дополнительных картинок в соответствующем свойстве

// TODO Сохранять свойства по отдельности ----КОЛИЧЕСТВО АТРИБУТОВ ВАРЬИРУЕТСЯ, писать буквенные ключи !!!!!!!!!!

// TODO Сохранять базовую цену, либо сразу цены со скидкой

// TODO Имя товара должно быть образовано из ТП, либо собирать по другому?

// TODO разграничить случаи ADD и UPDATE

// TODO Размер нужно вырезать в парсере из свойства Размер, поскольку там еще артикул и сезон, РАЗМЕР - свойство типа список!
// Бренд - свойство товара типа список

// Получаем значения размеров

$dbRes = CIBlockProperty::GetPropertyEnum(120,
	[], []
);

while ($res = $dbRes->GetNext()) {
	$sizePropArray[] = $res;
}

echo "<pre>";
//print_r($sizePropArray);
echo "</pre>";


// Начало цикла обработки массива-------------------------------------------------------------------------------------//

foreach ($firstTenElements as $key => $element) {

	// Цена торгового предложения
	$offerPrice = $element["PRICE"];

	$obElement = new CIBlockElement;

	$itemFieldsArray = [
//	"MODIFIED_BY" => $USER->GetID(),
		"IBLOCK_ID" => $IBlockCatalogId,
		"IBLOCK_SECTION_ID" => 345,
//	"PROPERTY_VALUES" => $element["ATTRIBUTES"],
		"NAME" => $element["NAME"],
		"CODE" => CUtil::translit($element["NAME"] . random_int(1, 100000), "ru", $translitParams),
		"ACTIVE" => "Y",
//	"PREVIEW_TEXT" => "",
//	"DETAIL_TEXT" => $element["DESCRIPTION"],
		"DETAIL_PICTURE" => CFile::MakeFileArray($element["PICTURES"][0]),
		'MORE_PHOTO' => CFile::MakeFileArray($element['PICTURES'])
	];



//	echo "<pre>";
//	print_r($obElement);
//	echo "</pre>";


	if ($productId = $obElement->Add($itemFieldsArray)) {
		echo "Добавлен товар " . $productId . "<br>";
	} else {
		echo "Ошибка: " . $obElement->LAST_ERROR . "<br>";
	}

	if ($productId) {
		$obElement = new CIBlockElement();

		// привязка свойств ТП к товару
		$arOfferProps = [
			$SKUPropertyId => $productId,
			'SIZE' => $element['ATTRIBUTES']['Размер'],

		];

		$arOfferFields = [
			'NAME' => $element["NAME"],
			'IBLOCK_ID' => SKU_IBLOCK_ID,
			'ACTIVE' => 'Y',
			"DETAIL_TEXT" => $element["DESCRIPTION"],
			'PROPERTY_VALUES' => $arOfferProps
		];


		// Получаем ID торгового предложения
		$offerId = $obElement->Add($arOfferFields);

		if ($offerId) {
			// Добавляем как товар и установим цену -----------------------НДС входит в цену   ??
			$catalogProductAddResult = CCatalogProduct::Add([
				"ID" => $offerId,
				'QUANTITY' => '5',
				"VAT_INCLUDED" => "Y"
			]);


			if (!$catalogProductAddResult) {
				throw new Exception("Ошибка добавление полей торгового предложения \"{$offerId}\"");
			}

			if ($catalogProductAddResult && !CPrice::SetBasePrice($offerId, $offerPrice, "RUB")) {
				throw new Exception("Ошибка установки цены торгового предложения \"{$offerId}\"");
			}
		} else {
			throw new Exception("Ошибка добавления торгового предложения: " . $obElement->LAST_ERROR);
		}
	} else {
		throw new Exception("Ошибка добавления товара: " . $obElement->LAST_ERROR);
	}
}
// Конец цикла обработки массива--------------------------------------------------------------------------------------//

*/


//--------------------------------------КОНЕЦ СОХРАНЕНИЯ (ADD) ЭЛЕМЕНТОВ----------------------------------------------//


//--------------------------------------ОБНОВЛЕНИЕ (UPDATE) ЭЛЕМЕНТОВ-------------------------------------------------//

?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>