<?php

$params = [
	"IBLOCK_ID" => CATALOG_IBLOCK_ID,
	"SECTION_ID" => TEMP_CATALOG_SECTION
];

$catalogSkus = $catalogItems->getList($params)
	->getItemsIds()
	->getSkusList(["CODE" => ["P_KITERU_EXTERNAL_OFFER_ID"]])
	->getSkusListFlatten()->skusListFlatten;

// Есть смысл выносить в метод класса Price?
foreach ($catalogSkus as $skuKey => $skuValue) {
	$skusPrices[] = CPrice::GetBasePrice($skuKey);
}
// TODO
// Перенести общую для всех апдейтов выборку в подходящее место
// Перед обновлением цен убедимся что внешние ключи заполнены
Parser\Utils\ExternalOfferId::updateExternalOfferId($catalogSkus, $resultArray, "P_KITERU_EXTERNAL_OFFER_ID");

echo PHP_EOL;
echo "Количество торговых предложений в инфоблоке, для которых будут обновлены цены: " . count($catalogSkus) . PHP_EOL;
echo PHP_EOL;

// Добавляем в массив торговых предложений цены
$catalogSkus = Parser\Catalog\Prices::prepare($catalogSkus, $skusPrices);
// Обновляем цены у всех ТП временного раздела
Parser\Catalog\Prices::update($catalogSkus, $resultArray);
//file_put_contents(__DIR__ . "/logs/console__catalogSkus--afterPricesPrepare.log", print_r($catalogSkus, true));
