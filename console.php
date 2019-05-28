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

if (!Loader::includeModule('iblock')) {
    die('Не удалось загрузить модуль инфоблоки');
}

if (!Loader::includeModule('catalog')) {
    die('Невозможно загрузить модуль торгового каталога');
}

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
}

$resultArray = []; // результат парсинга нового полученного XML-каталога с сайта-донора
$addArray = []; // массив товаров, которые будут добавлены в каталог
$catalogItemsExternalIds = []; // Внешние ключи товаров каталога
$newItems = []; // Массив новых товаров для отправки почтового уведомления менеджерам
$notAvailableOffersExternalIds = []; // Массив внешних ключей ТП с available = false в XML
$availableOffersExternalIds = []; // Массив внешних ключей ТП с available = true в XML
$notAvailableOffers = [];
$availableOffers = [];
$notAvailableOffersIds = [];
$availableOffersIds = [];
$crawler = null; // объект компонента Symfony
$result = null; // Результат отправки почтового уведомления менеждерам

// Конфигурируем объект для работы с сохраненными элементами каталога
$sectionParams = new Parser\SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION, SKU_IBLOCK_ID);
// Создаем объект для работы с товарами временного раздела
$items = new Parser\Catalog\Items($sectionParams);
// Создаем экземпляр источника, фактически это путь к каталогу товаров на сайте-источнике
$source = new Source(SOURCE);
// Получаем содержание каталога с сайта-источника, которое и будем парсить
$xml = $source->getSource();
// Создаем краулер для нового каталога
$crawler = new Crawler($xml);
// Парсим новый XML каталог
$resultArray = ParserBody::parse($crawler);

//TEMP
//$resultArray = array_slice($resultArray, 30, 30, true); // Для отладки
//ENDTEMP

// Парсим каталог XLS
$xlsResult = Parser\XlsParser\XlsParser::parse();

// TODO работу по слиянию массивов вынести в отдельный метод
foreach ($resultArray as $resultKey => $resultValue) {
    foreach ($resultValue as $offerKey => $offerValue) {
        foreach ($xlsResult as $xlsKey => $xlsValue) {
            if ($xlsValue[0] == $offerValue["VENDOR_CODE"]) {
                $resultArray[$resultKey][$offerKey]["QUANTITY"] = $xlsValue[2];
                $resultArray[$resultKey][$offerKey]["DISCOUNT_PERCENT"] = $xlsValue[3];
                $resultArray[$resultKey][$offerKey]["DISCOUNT_VALUE"] =
                    round(floatval($resultArray[$resultKey][$offerKey]["PRICE"]), 2)
                    * ((int)$resultArray[$resultKey][$offerKey]["DISCOUNT_PERCENT"] / 100);
                $resultArray[$resultKey][$offerKey]["DISCOUNT_PRICE"] =
                    round(floatval($resultArray[$resultKey][$offerKey]["PRICE"]), 2)
                    - round(floatval($resultArray[$resultKey][$offerKey]["DISCOUNT_VALUE"]), 2);
                if (in_array((int)$resultArray[$resultKey][$offerKey]['CATEGORY_ID'], SUMMER)) {
                    $resultArray[$resultKey][$offerKey]["SEASON_PRICE"] = round($resultArray[$resultKey][$offerKey]["DISCOUNT_PRICE"] * 1.5, 2);
                } elseif (in_array((int)$resultArray[$resultKey][$offerKey]['CATEGORY_ID'], WINTER)) {
                    $resultArray[$resultKey][$offerKey]["SEASON_PRICE"] = round($resultArray[$resultKey][$offerKey]["DISCOUNT_PRICE"] * 1.6, 2);
                }

                if($resultArray[$resultKey][$offerKey]["SEASON_PRICE"] == 0){
                    echo $resultArray[$resultKey][$offerKey]["DISCOUNT_PRICE"] . $resultArray[$resultKey][$offerKey]["NAME"];
                }
                if ($resultArray[$resultKey][$offerKey]["DISCOUNT_PERCENT"] == false) {
                    echo "Отсутствует скидка на товар: {$resultArray[$resultKey][$offerKey]["NAME"]}" . PHP_EOL;
                    echo "Его сезонная цена составит {$resultArray[$resultKey][$offerKey]["SEASON_PRICE"]} руб." . PHP_EOL;
                }
            }
        }
    }
}

//file_put_contents(__DIR__ . "/logs/resultArray.log", print_r($resultArray, true));
// Создаем свойство для хранения внешнего ключа товара, если оно не существует
Parser\Catalog\Properties::createExternalItemIdProperty(
    [
        "IBLOCK_ID" => CATALOG_IBLOCK_ID,
        "NAME" => "Идентификатор товара в каталоге skiboard.ru",
        "CODE" => "P_SKIBOARD_GROUP_ID"
    ]
);

// Создаем свойство для хранения внешнего ключа торгового предложения, если оно не существует
Parser\Catalog\Properties::createExternalItemIdProperty(
    [
        "IBLOCK_ID" => SKU_IBLOCK_ID,
        "NAME" => "Идентификатор торгового предложения в каталоге skiboard.ru",
        "CODE" => "SKIBOARD_EXTERNAL_OFFER_ID"
    ]
);

// Массив товаров временного раздела
$catalogItems = $items->getList([], ["PROPERTY_P_SKIBOARD_GROUP_ID"])->list;
// Очищает результаты предыдущей выборки
$items->reset();

foreach ($catalogItems as $item) {
    if (strlen($item["PROPERTY_P_SKIBOARD_GROUP_ID_VALUE"]) > 0) {
        $catalogItemsExternalIds[] = $item["PROPERTY_P_SKIBOARD_GROUP_ID_VALUE"];
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
// Товары (внешние ключи), торговые предложения которых будут установлены в 0 (отсутствуют в XML)
$differenceDisable = array_values(array_diff($catalogItemsExternalIds, $resultArrayKeys));
$differenceDisableCount = count($differenceDisable);
// Разделим торговые предложения на недоступные и доступные
foreach ($resultArray as $key => $item) {
    foreach ($item as $offerKey => $offerValue) {
        if ($offerValue["AVAILABLE"] == "false" && !in_array($offerValue["OFFER_ID"], $notAvailableOffersExternalIds)) {
            $notAvailableOffersExternalIds[] = $offerValue["OFFER_ID"];
        } elseif ($offerValue["AVAILABLE"] == "true" && !in_array($offerValue["OFFER_ID"], $availableOffersExternalIds)) {
            $availableOffersExternalIds[] = $offerValue["OFFER_ID"];
        }
    }
}

// Товары (внешние ключи), торговые предложения которых будут установлены в QUANTITY.
// (Все товары, кроме отключаемых).
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
//file_put_contents(__DIR__ . "/logs/console__notAvailable.log", print_r($notAvailable, true));
// Массив торговых предложений временного раздела
$catalogSkus = $items->getList()
    ->getItemsIds()
    ->getSkusList(["CODE" => ["SKIBOARD_EXTERNAL_OFFER_ID"]])
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
    }
} else {
    echo PHP_EOL;
    echo "Выгрузка и каталог совпадают, обновление не требуется" . PHP_EOL;
    echo PHP_EOL;
    return;
}

file_put_contents(__DIR__ . "/logs/addArray.log", print_r($addArray, true));

if ($differenceDisableCount > 0) {

    $filter = [
        "PROPERTY_P_SKIBOARD_GROUP_ID" => $differenceDisable
    ];

    $props = [
        "PROPERTY_P_SKIBOARD_GROUP_ID"
    ];

    $disableSkusList = $items->getList($filter, $props)
        ->getItemsIds()
        ->getSkusList(["CODE" => ["SKIBOARD_EXTERNAL_OFFER_ID"]])
        ->getSkusListFlatten()
        ->skusListFlatten;

    $items->reset();

    foreach ($disableSkusList as $itemKey => $itemValue) {
        if ($itemValue["QUANTITY"] > 0) {
            CCatalogProduct::Update($itemKey, ["QUANTITY" => 0]);
            echo "Количество отсутствующего в новом прайсе ТП {$itemKey} - {$itemValue["NAME"]} установлено в 0" . PHP_EOL;
        }
    }
    echo PHP_EOL;
}

if ($restoreQuantityItemsCount > 0) {
    $filter = [
        "PROPERTY_P_SKIBOARD_GROUP_ID" => $restoreQuantityItems
    ];

    $props = [
        "PROPERTY_P_SKIBOARD_GROUP_ID"
    ];

    $restoreQuantitySkusList = $items->getList($filter, $props)
        ->getItemsIds()
        ->getSkusList(["CODE" => ["SKIBOARD_EXTERNAL_OFFER_ID"]])
        ->getSkusListFlatten()
        ->skusListFlatten;

    $items->reset();

    foreach ($resultArray as $itemKey => $itemValue) {
        foreach ($itemValue as $offerKey => $offerValue) {
            foreach ($restoreQuantitySkusList as $restoreKey => $restoreValue) {
                if ($offerValue["AVAILABLE"] == "true" && $offerValue["OFFER_ID"] == $restoreValue["PROPERTIES"]["SKIBOARD_EXTERNAL_OFFER_ID"]["VALUE"]) {
                    $restoreQuantitySkusList[$restoreKey]["XML_QUANTITY"] = $offerValue["QUANTITY"];
                }
            }
        }
    }

//file_put_contents(__DIR__ . "/logs/console__restoreQuantitySkusList.log", print_r($restoreQuantitySkusList, true));

    foreach ($restoreQuantitySkusList as $itemKey => $itemValue) {
        if ($itemValue["XML_QUANTITY"] > 0) {
            CCatalogProduct::Update($itemKey, ["QUANTITY" => $itemValue["XML_QUANTITY"]]);
            echo "Доступное количество ТП {$itemKey} - {$itemValue["NAME"]} обновлено до {$itemValue["XML_QUANTITY"]} шт." . PHP_EOL;
        }
    }
}

// Количество ТП, у которых в XML available=false установим в 0
$notAvailableOffersDb = CIblockElement::GetList(
    [],
    [
        "IBLOCK_ID" => SKU_IBLOCK_ID,
        "PROPERTY_SKIBOARD_EXTERNAL_OFFER_ID" => $notAvailableOffersExternalIds
    ],
    false,
    false,
    [
        "IBLOCK_ID",
        "ID",
        "NAME"
    ]
);

while ($res = $notAvailableOffersDb->GetNext()) {
    $notAvailableOffers[] = $res;
}

//file_put_contents(__DIR__ . "/logs/console__notAvailableOffers.log", print_r($notAvailableOffers, true));

foreach ($notAvailableOffers as $offerKey => $offerValue) {
    CCatalogProduct::Update($offerValue["ID"], ["QUANTITY" => 0]);
    echo "Кол-во недоступного ТП {$offerValue["ID"]} {$offerValue["NAME"]} установлено в 0";
    echo PHP_EOL;
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

$dbRes = CIBlockProperty::GetPropertyEnum(SIZE_PROPERTY_ID);

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

$dbRes = CIBlockProperty::GetPropertyEnum(SIZE_PROPERTY_ID);

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

// Записываем бренды в HL
foreach ($sourceBrandsArray as $brandId => $brand) {
    if (!in_array(CUtil::translit($brand, 'ru', $translitParams), $manufacturerXmlIds)) {
        $result = $dataClass::add(
            [
                "UF_NAME" => strtoupper($sourceBrandsArray[$brandId]),
                "UF_XML_ID" => CUtil::translit($sourceBrandsArray[$brandId], 'ru', $translitParams),
                "UF_LINK" => "/brands/" . strtolower(CUtil::translit($sourceBrandsArray[$brandId], 'ru', $translitParams)) . "/",
            ]
        );
        echo "В справочник добавлен новый производитель ID = " . $result->getId() . PHP_EOL;
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
    $result = \Parser\Mail::sendNewItems($newItems);
}

if ($result && $result->isSuccess()) {
    echo "Уведомление о {$newItemsLength} новых товарах успешно отправлено " . PHP_EOL;
}

// Обновляем цены всех торговых предложений
//require_once(__DIR__ . "/update_prices.php");
// Сохраняем текущий XML
echo Storage::storeCurrentXml($source);
// Завершаем скрипт и выводим статистику
register_shutdown_function(function () {
    global $startExecTime;
    $elapsedMemory = (!function_exists('memory_get_usage'))
        ? '-'
        : round(memory_get_usage() / 1024 / 1024, 2) . ' MB';
    echo "\nВремя работы скрипта: " . (getmicrotime() - $startExecTime) . " сек\n";
    echo "Использованная память: " . $elapsedMemory . PHP_EOL;
});

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
