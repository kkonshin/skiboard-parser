#!/usr/bin/php

<?php

if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

require(__DIR__ . "/../config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once(__DIR__ . "/../vendor/autoload.php");

global $categoryToSection;

while (ob_get_level()) {
	ob_end_flush();
}
$itemsIds = [];
$doublesList = [];
$params = new Parser\SectionParams(CATALOG_IBLOCK_ID, TEMP_CATALOG_SECTION);
$items = new Parser\Catalog\Items($params);

$doublesFilter = array(
	0 => 'Баллон Slingshot 2014 - 2015 Rally Bladders - Strut%',
	1 => 'Баллон Slingshot 2014 - 2015 Rally LE Bladders%',
	2 => 'Баллон Slingshot 2011-2014 RPM LE Bladders%',
	3 => 'Кайтсерфборд Slingshot 2015 Alien Twitster FX%',
	4 => 'Гидрокостюм Body Glove 2015 Method 2.0 Bk/Zip 3/2 Fullsuit Green%',
	5 => 'Гидрокостюм Body Glove 2015 Pro3 3/2 Fullsuit Black/Grey%',
	6 => 'Гидрокостюм Body Glove 2015 Pro3 3/2 Fullsuit Black/Red%',
	7 => 'Гидрокостюм Body Glove 2015 Pro3 3/2 Fullsuit Grey/Lime%',
	8 => 'Гидрокостюм Body Glove 2015 Siroko Bk/Zip 4/3 Fullsuit Red%',
	9 => 'Гидрокостюм Body Glove 2015 (Женский) Pro3 2/1MM Springsuit Shoty Black%',
	10 => 'Гидрокостюм Body Glove 2015 Torque Combo 3/3 Red%',
	11 => 'Гидромайка Body Glove 2015 Basic L/A Fitted Rashguard%',
	12 => 'Гидромайка Body Glove 2015 Performance Loosefit L/A Shirt%',
	13 => 'Гидромайка Body Glove 2015 Prime L/A 6oz Fitted Rashguard%',
	14 => 'Гидрообувь Body Glove 2015 Prime Round Toe Bootie 5mm%',
	15 => 'Гидрошлем Body Glove 2015 Super Beanie Neoprene Beanie 3mm%',
	16 => 'Перчатки Body Glove 2015 Pr1me 5 Finger Glove 3mm%',
	17 => 'Гидрошорты Body Glove 2015 Fusion Men\'s Pullover Short%',
	18 => 'Баллон Slingshot 2015-2016 RPM LE Bladder%',
	19 => 'Баллон Slingshot 2011 - 2015 RPM Strut Bladder Set%',
	20 => 'Футболка Slingshot 2015 First Tee Burgundy%',
	21 => 'Футболка Slingshot 2015 First Tee Royal Blue%',
	22 => 'Футболка Slingshot 2015 USA Wood Core Tee Grey%',
	23 => 'Кайтборд Slingshot 2016 Asylum%',
	24 => 'Слайдербар Slider Bar RideEngine Carbon (8" (20,32 см))%',
	25 => 'Гидрообувь Body Glove 2016 CT Covered Split Toe Bootie 3mm%',
	26 => 'Баллон Slingshot 2015 Turbine LE Bladder%',
	27 => 'Планка Slingshot 2017 Compstick w/ Guardian%',
	28 => 'Планка Slingshot 2017 Compstick w/ Sentinel%',
	29 => 'Кайтборд Slingshot 2017 Vision%',
	30 => 'Кайт Slingshot 2017 Turbine%',
	31 => 'Кайтборд Shaman Furor Dog (Board Only, 139 cm x 43 cm, tails 32)%',
	32 => 'Кайт Трапеция Slingshot Ballistic Harness Lemon%',
	33 => 'Кайт Трапеция RideEngine 2017 Bamboo Elite Harness%',
	34 => 'Слайдербар RideEngine 2017 Metal Sliding Bar%',
	35 => 'Кайт Трапеция RideEngine 2017 Hex Core Space Grape Harness%',
	36 => 'Кайт Slingshot 2018 Turbine%',
	37 => 'Кайт Трапеция RideEngine 2018 3k Carbon Elite Harness%',
	38 => 'Кайт Трапеция RideEngine 2018 Hex Core Sea Engine Green Harness%',
	39 => 'Кайт Трапеция RideEngine 2018 Silver Carbon Elite Harness%',
	40 => 'Крюк RideEngine 2018 Kite Fixed Hook%',
	41 => 'Крюк RideEngine 2018 Windsurf Fixed Hook%',
	42 => 'Слайдербар RideEngine 2018 Metal Sliding Bar%',
	43 => 'Кайт Slingshot 2019 Rally%',
	44 => 'Кайт Трапеция RideEngine 2019 Elite Carbon Infrared Harness%',
	45 => 'Кайт Трапеция RideEngine 2019 Elite Carbon Sea Engine Green Harness%',
	46 => 'Кайт Трапеция RideEngine 2019 Elite Carbon White Harness%',
	47 => 'Кайт Трапеция RideEngine 2019 Prime Coast Harness%',
	48 => 'Кайт Трапеция RideEngine 2019 Prime Deep Sea Harness%',
	49 => 'Кайт Трапеция RideEngine 2019 Prime Island Time Harness%',
	50 => 'Кайт Трапеция RideEngine 2019 Prime Pacific Mist Harness%',
	51 => 'Гидрокостюм RideEngine 2019 Silo 5/4 BZ full%',
	52 => 'Гидромайка RideEngine 2019 Cora 2mm Tank Top%',
	53 => 'Гидрообувь RideEngine 2019 4mm Aire Booties%',
	54 => 'Гидрошорты RideEngine 2019 Harlo 2mm Shorts%',
	55 => 'Неопреновая куртка RideEngine Layover 2.5mm Neo Hoodie%',
	56 => 'Перчатки RideEngine 2018 2mm Gloves%',
);
$itemsList = $items->getList(["NAME" => $doublesFilter, "SECTION_ID" => ''], ["PROPERTY_CATEGORY_ID"])->list;
//$itemsList = $items->getList(["NAME" => $doublesFilter], ["PROPERTY_CATEGORY_ID"])->list;

foreach ($itemsList as $item) {
//    if($item["IBLOCK_SECTION_ID"] != TEMP_CATALOG_SECTION) {
	$itemsIds[$item["ID"]] = $item["ID"];
//	}
}

$dbRes = \CIBlockElement::GetElementGroups($itemsIds, false, ["ID", "CODE", "NAME", "IBLOCK_ELEMENT_ID"]);

while ($res = $dbRes->GetNext()) {
	$doublesList[$res["IBLOCK_ELEMENT_ID"]][] = $res;
}

$items->reset();

$filterKeys = array_keys($doublesList);

$itemsToAdd = $items->getList(["ID" => $filterKeys, "SECTION_ID" => ''], ["PROPERTY_CATEGORY_ID"])->list;

foreach ($doublesList as $doubleKey => $doubleValue) {
	foreach ($doubleValue as $doubleValueKey => $doubleValueValue) {
		foreach ($itemsToAdd as $itemKey => $itemValue) {
			if ($doubleValueValue["IBLOCK_ELEMENT_ID"] == $itemValue["ID"]) {
				$doublesList[$doubleKey][$doubleValueKey]["IBLOCK_ELEMENT_NAME"] = $itemValue["NAME"];
				$doublesList[$doubleKey][$doubleValueKey]["IBLOCK_ELEMENT_CODE"] = $itemValue["CODE"];
			}
		}
	}
}

$bindingsArray = [];

foreach ($doublesList as $doubleKey => $doubleValue) {
	foreach ($doubleValue as $doubleValueKey => $doubleValueValue) {
        $bindingsArray[trim($doubleValueValue["IBLOCK_ELEMENT_NAME"])][$doubleValueValue["IBLOCK_ELEMENT_ID"]][] = $doubleValueValue["ID"];
	}
}


file_put_contents(__DIR__ . "/../logs/bind_itemsList.log", print_r($itemsList, true));
file_put_contents(__DIR__ . "/../logs/bind_itemsIds.log", print_r($itemsIds, true));
file_put_contents(__DIR__ . "/../logs/bind_itemsToAdd.log", print_r($itemsToAdd, true));

file_put_contents(__DIR__ . "/../logs/bind_bindingsArray.log", print_r($bindingsArray, true));
file_put_contents(__DIR__ . "/../logs/bind_bindingsArray--count.log", print_r(count($bindingsArray), true));

file_put_contents(__DIR__ . "/../logs/bind_doublesList.log", print_r($doublesList, true));
file_put_contents(__DIR__ . "/../logs/bind_doublesList--count.log", print_r(count($doublesList), true));

// Только для SKIBOARD!
// Привязка всех товаров временного раздела к разделам в зависимости от таблицы $categoryToSection из файла конфигурации
//Parser\Utils\BindSections::bind($itemsList, $categoryToSection, TEMP_CATALOG_SECTION, CATALOG_IBLOCK_ID);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
