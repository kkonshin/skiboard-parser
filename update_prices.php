<?php

global $resultArray;
global $catalogSkus;

echo PHP_EOL;
echo "Проверяем наличие внешних ключей ТП и обновляем цены" . PHP_EOL;
echo PHP_EOL;

$skusPrices = []; // Массив цен торговых предложений, которые будут обновлены

foreach ($catalogSkus as $skuKey => $skuValue) {
	$skusPrices[] = CPrice::GetBasePrice($skuKey);
}
// Перед обновлением цен убедимся что внешние ключи заполнены
// Обновятся только ТП, которые есть в новом прайсе
Parser\Utils\ExternalOfferId::updateExternalOfferId($catalogSkus, $resultArray, "P_KITERU_EXTERNAL_OFFER_ID");
// Добавляем в массив торговых предложений цены
$catalogSkus = Parser\Catalog\Prices::prepare($catalogSkus, $skusPrices);
// Обновляем цены у всех ТП временного раздела
Parser\Catalog\Prices::update($catalogSkus, $resultArray);
//file_put_contents(__DIR__ . "/logs/console__catalogSkus--afterPricesPrepare.log", print_r($catalogSkus, true));
