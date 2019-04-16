<?php

namespace Parser\Catalog;

class Prices
{
	/**
	 * Добавляет цены к массиву ТП
	 * @param array $catalogSkus
	 * @param array $skusPrices
	 * @return array
	 */


	// TODO сначала запишем укороченный старый прайс в новый тестовый раздел
	// - загружаем подготовленные файлы
	// - меняем пути в конфиге
	// - ограничиваем размер resultArray
	// - отключаем парсер HTML
	// - раскомментируем add.php


	public static function prepare(Array $catalogSkus, Array $skusPrices)
	{
		foreach ($catalogSkus as $skuKey => $skuValue) {
			foreach ($skusPrices as $priceKey => $priceValue) {
				if ($skuValue["ID"] == $priceValue["PRODUCT_ID"]) {
					$catalogSkus[$skuKey]["PRICE"] = $priceValue["PRICE"];
				}
			}
		}
		return $catalogSkus;
	}

	/**
	 * @param array $catalogSkus
	 * @param array $resultArray
	 */
	public static function update(Array $catalogSkus, Array $resultArray)
	{

		// TODO сюда передавать подготовленный массив $catalogSkus

		file_put_contents(__DIR__ . "/../../logs/prices__catalogSkus.log", print_r($catalogSkus, true));
		file_put_contents(__DIR__ . "/../../logs/prices__resultArray.log", print_r($resultArray, true));

		// Для полученных из каталога ТП в массиве resultArray ищем соответствующие товары
		// Необходимо добавить в массив ТП ключ, по которому можно связать ТП из resultArray и ТП из каталога
		// Фактически ТП сейчас никак не связаны с XML

		// Цены должны обновляться после определения отсутствующих товаров
		// [OFFER_ID] => 50693

		foreach ($catalogSkus as $offerIdKey => $offerIdValue) {

			foreach ($resultArray as $resultKey => $resultItem) {

				foreach ($resultItem as $offerKey => $offerValue) {

					if ($offerValue["OFFER_ID"] == $offerIdValue["ID"]) {

//						echo $offerValue["OFFER_ID"] . "=" . $offerIdValue["ID"] . PHP_EOL;

						$tmpPriceId = null;

						$cp = new \CPrice();

						$dbres = $cp->GetList(
							[],
							[
								"PRODUCT_ID" => $offerIdValue["ID"]
							],
							false,
							false, ["ID"]
						);

						while ($res = $dbres->GetNext()) {
							$tmpPriceId = $res;
						}

						echo "Обновлена цена {$offerValue["SEASON_PRICE"]} для товарного предложения {$offerIdValue["ID"]} "
							. \CPrice::Update(
								$tmpPriceId["ID"],
								[
									"PRODUCT_ID" => $offerIdValue["ID"],
									"PRICE" => $offerValue["SEASON_PRICE"],
									"CURRENCY" => "RUB"
								]
							);

						echo PHP_EOL;
					}

				}

			}

		}
//		file_put_contents(__DIR__ . "/../../logs/console__skusPrices.log", print_r($tmpPricesIds, true));
	}

	public static function update__skiboard(array $catalogSkusWithoutParent, array $resultArray)
	{
		foreach ($catalogSkusWithoutParent as $offerIdKey => $offerIdValue) {
			foreach ($resultArray as $resultKey => $resultItem) {
				foreach ($resultItem as $offerKey => $offerValue) {
					if ($offerValue["OFFER_ID"] === $offerIdValue["PROPERTIES"]["SKIBOARD_EXTERNAL_OFFER_ID"]["VALUE"]) {
						if ($offerValue["SEASON_PRICE"] !== $offerIdValue["PRICE"]) {

							$tmpPriceId = null;

							$cp = new \CPrice();

							$dbres = $cp->GetList([], ["PRODUCT_ID" => $offerIdValue["ID"]], false, false, ["ID"]);

							while ($res = $dbres->GetNext()) {
								$tmpPriceId = $res;
							}

							echo "Обновлена цена {$offerValue["SEASON_PRICE"]} для товарного предложения {$offerIdValue["ID"]} "
								. \CPrice::Update(
									$tmpPriceId["ID"],
									[
										"PRODUCT_ID" => $offerIdValue["ID"],
										"PRICE" => $offerValue["SEASON_PRICE"],
										"CURRENCY" => "RUB"
									]
								)
								. PHP_EOL;
						}
					}
				}
			}
		}
	}
}