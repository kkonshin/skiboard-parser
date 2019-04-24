<?php
// Есть смысл выносить в метод класса Price?
foreach ($catalogSkus as $skuKey => $skuValue) {
	$skusPrices[] = CPrice::GetBasePrice($skuKey);
}
// TODO
// Перенести общую для всех апдейтов выборку в подходящее место
// Перед обновлением цен убедимся что внешние ключи заполнены
// Обновятся только ТП, которые есть в новом прайсе
Parser\Utils\ExternalOfferId::updateExternalOfferId__skiboard($catalogSkus, $resultArray);

// FIXME найти пересечение $catalogSkus и массива ТП из XML
//echo PHP_EOL;
//echo "Количество торговых предложений в инфоблоке, для которых будут обновлены цены: " . count($catalogSkus) . PHP_EOL;
//echo PHP_EOL;

// Добавляем в массив торговых предложений цены
$catalogSkus = Parser\Catalog\Prices::prepare($catalogSkus, $skusPrices);
// Обновляем цены у всех ТП временного раздела, которые есть в новом прайсе
Parser\Catalog\Prices::update__skiboard($catalogSkus, $resultArray);
//file_put_contents(__DIR__ . "/logs/console__catalogSkus--afterPricesPrepare.log", print_r($catalogSkus, true));
