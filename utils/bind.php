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
$doublesFilter = [
	"%Баллон Slingshot 2014 - 2015 Rally Bladders - Strut ",
	"%Баллон Slingshot 2014 - 2015 Rally LE Bladders ",
	"%Баллон Slingshot 2011-2014 RPM LE Bladders ",
	"%Кайтсерфборд Slingshot 2015 Alien Twitster FX ",
	"%Гидрокостюм Body Glove 2015 Method 2.0 Bk/Zip 3/2 Fullsuit Green ",
	"%Гидрокостюм Body Glove 2015 Pro3 3/2 Fullsuit Black/Grey ",
	"%Гидрокостюм Body Glove 2015 Pro3 3/2 Fullsuit Black/Red ",
	"%Гидрокостюм Body Glove 2015 Pro3 3/2 Fullsuit Grey/Lime ",
	"%Гидрокостюм Body Glove 2015 Siroko Bk/Zip 4/3 Fullsuit Red ",
	"%Гидрокостюм Body Glove 2015 (Женский) Pro3 2/1MM Springsuit Shoty Black ",
	"%Гидрокостюм Body Glove 2015 Torque Combo 3/3 Red ",
	"%Гидромайка Body Glove 2015 Basic L/A Fitted Rashguard ",
	"%Гидромайка Body Glove 2015 Performance Loosefit L/A Shirt ",
	"%Гидромайка Body Glove 2015 Prime L/A 6oz Fitted Rashguard ",
	"%Гидрообувь Body Glove 2015 CT Covered Split Toe Bootie 3mm (9 (41-42))",
	"%Гидрообувь Body Glove 2015 Prime Round Toe Bootie 5mm ",
	"%Гидрошлем Body Glove 2015 Super Beanie Neoprene Beanie 3mm ",
	"%Перчатки Body Glove 2015 Pr1me 5 Finger Glove 3mm ",
	"%Гидрошорты Body Glove 2015 Fusion Men's Pullover Short ",
	"%Баллон Slingshot 2015-2016 RPM LE Bladder ",
	"%Баллон Slingshot 2011 - 2015 RPM Strut Bladder Set ",
	"%Футболка Slingshot 2015 First Tee Burgundy ",
	"%Футболка Slingshot 2015 First Tee Royal Blue ",
	"%Футболка Slingshot 2015 USA Wood Core Tee Grey ",
	"%Кайтборд Slingshot 2016 Asylum ",
	"%Слайдербар Slider Bar RideEngine Carbon (8'' (20,32 см))",
	"%Гидрообувь Body Glove 2016 CT Covered Split Toe Bootie 3mm ",
	"%Баллон Slingshot 2015 Turbine LE Bladder ",
	"%Планка Slingshot 2017 Compstick w/ Guardian ",
	"%Планка Slingshot 2017 Compstick w/ Sentinel ",
	"%Кайтборд Slingshot 2017 Vision ",
	"%Кайт Slingshot 2017 Turbine ",
	"%Кайтборд Shaman Furor Dog (Board Only, 139 cm x 43 cm, tails 32)",
	"%Кайт Трапеция Slingshot Ballistic Harness Lemon ",
	"%Кайт Трапеция RideEngine 2017 Bamboo Elite Harness ",
	"%Слайдербар RideEngine 2017 Metal Sliding Bar ",
	"%Кайт Трапеция RideEngine 2017 Hex Core Space Grape Harness ",
	"%Кайт Slingshot 2018 Turbine ",
	"%Кайт Трапеция RideEngine 2018 3k Carbon Elite Harness ",
	"%Кайт Трапеция RideEngine 2018 Hex Core Sea Engine Green Harness ",
	"%Кайт Трапеция RideEngine 2018 Silver Carbon Elite Harness ",
	"%Крюк RideEngine 2018 Kite Fixed Hook ",
	"%Крюк RideEngine 2018 Windsurf Fixed Hook ",
	"%Слайдербар RideEngine 2018 Metal Sliding Bar ",
	"%Кайт Slingshot 2019 Rally ",
	"%Кайт Трапеция RideEngine 2019 Elite Carbon Infrared Harness ",
	"%Кайт Трапеция RideEngine 2019 Elite Carbon Sea Engine Green Harness ",
	"%Кайт Трапеция RideEngine 2019 Elite Carbon White Harness ",
	"%Кайт Трапеция RideEngine 2019 Prime Coast Harness ",
	"%Кайт Трапеция RideEngine 2019 Prime Deep Sea Harness ",
	"%Кайт Трапеция RideEngine 2019 Prime Island Time Harness ",
	"%Кайт Трапеция RideEngine 2019 Prime Pacific Mist Harness ",
	"%Гидрокостюм RideEngine 2019 Silo 5/4 BZ full ",
	"%Гидромайка RideEngine 2019 Cora 2mm Tank Top ",
	"%Гидрообувь RideEngine 2019 4mm Aire Booties ",
	"%Гидрошорты RideEngine 2019 Harlo 2mm Shorts ",
	"%Неопреновая куртка RideEngine Layover 2.5mm Neo Hoodie ",
	"%Перчатки RideEngine 2018 2mm Gloves ",
];

$itemsList = $items->getList(["NAME" => $doublesFilter], ["PROPERTY_CATEGORY_ID"])->list;
foreach ($itemsList as $item) {
	$itemsIds[$item["ID"]] = $item["ID"];
}
$dbRes = \CIBlockElement::GetElementGroups($itemsIds,false, ["ID", "CODE", "NAME", "IBLOCK_ELEMENT_ID"]);
while($res = $dbRes->GetNext()){
    $doublesList[$res["IBLOCK_ELEMENT_ID"]] = $res;
}
//file_put_contents(__DIR__ . "/../logs/bind_itemsList.log", print_r($itemsList, true));
//file_put_contents(__DIR__ . "/../logs/bind_itemsIds.log", print_r($itemsIds, true));
file_put_contents(__DIR__ . "/../logs/bind_doublesList.log", print_r($doublesList, true));
file_put_contents(__DIR__ . "/../logs/bind_doublesList--count.log", print_r(count($doublesList), true));

// Только для SKIBOARD!
// Привязка всех товаров временного раздела к разделам в зависимости от таблицы $categoryToSection из файла конфигурации
//Parser\Utils\BindSections::bind($itemsList, $categoryToSection, TEMP_CATALOG_SECTION, CATALOG_IBLOCK_ID);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
