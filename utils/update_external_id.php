#!/usr/bin/php

<?php

if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

require(__DIR__ . "/../config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once(__DIR__ . "/../vendor/autoload.php");

use Parser\SectionParams;
use Parser\Source\Source;
use Parser\Utils\ExternalOfferId;
use Parser\ParserBody\ParserBody;
use Symfony\Component\DomCrawler\Crawler;

while (ob_get_level()) {
	ob_end_flush();
}

$doublesFilter = array (
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

//$source = __DIR__ . "/../save/backup/13.02.2019/previous.xml";
$source = SOURCE;

// На рабочем! На тестовом задавать ID раздела
$tempCatalogSection = TEMP_CATALOG_SECTION;

$params = new SectionParams(CATALOG_IBLOCK_ID, $tempCatalogSection);

$items = new Parser\Catalog\Items($params);

$source = new Source($source);

$xml = $source->getSource();

$crawler = new Crawler($xml);

$resultArray = ParserBody::parse($crawler);

$extraProperties = [
	"PROPERTY_P_GROUP_ID",
];
$itemsList = $items->getList(["NAME" => $doublesFilter], $extraProperties)->list;

$items->reset();

$skusList = $items->getList()
	->getItemsIds()
	->getSkusList(["CODE" => ["P_KITERU_EXTERNAL_OFFER_ID"]])
	->getSkusListFlatten()
	->skusListFlatten;

$items->reset();

ExternalOfferId::updateExternalItemId($itemsList, $resultArray, "P_GROUP_ID", P_TRANSLIT_PARAMS);

ExternalOfferId::updateExternalOfferId($skusList, $resultArray, "P_KITERU_EXTERNAL_OFFER_ID");

file_put_contents(__DIR__ . "/../logs/update_external__resultArray--392.log", print_r($resultArray, true));
file_put_contents(__DIR__ . "/../logs/update_external__itemsList--392.log", print_r($itemsList, true));
file_put_contents(__DIR__ . "/../logs/update_external__skusList--392.log", print_r($skusList, true));

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
