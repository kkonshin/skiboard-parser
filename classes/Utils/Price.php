<?php

namespace Parser\Utils;

// FIXME этот класс работает только для skiboard - реализовать универсальный

// Убираем сезонную цену, для skiboard продумать либо наследование, либо просто отдельный метод

// TODO перенести в Catalog
// TODO записать 5-10 товаров из старого прайса, поменять цены в новом


class Price
{
	/**
	 * Добавляет цены к массиву ТП
	 * @param array $catalogSkus
	 * @param array $skusPrices
	 * @return array
	 */

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

		file_put_contents(__DIR__ . "/../../logs/prices__catalogSkus.log", print_r($catalogSkus, true));
		file_put_contents(__DIR__ . "/../../logs/prices__resultArray.log", print_r($resultArray, true));

		$tmpPricesIds = [];

		// TODO для массива ТП просто установим цены из распарсенного массива


		foreach ($catalogSkus as $offerIdKey => $offerIdValue) {

//			echo $offerIdValue["ID"] . PHP_EOL;

			foreach ($resultArray as $resultKey => $resultItem) {

				foreach ($resultItem as $offerKey => $offerValue) {

					echo $offerValue["OFFER_ID"] . PHP_EOL;

					if ($offerValue["OFFER_ID"] == $offerIdValue["ID"]) {

						echo $offerValue["OFFER_ID"] . "=" . $offerIdValue["ID"] . PHP_EOL;

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
								$tmpPricesIds[] = $res;
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
