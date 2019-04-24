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
	 * Обновление цен для всех ТП временного раздела которые присутствуют в новом файле XML
	 * @param array $catalogSkus
	 * @param array $resultArray
	 */
	public static function update(Array $catalogSkus, Array $resultArray, $externalOfferId)
	{
		foreach ($catalogSkus as $offerIdKey => $offerIdValue) {

			foreach ($resultArray as $resultKey => $resultItem) {

				foreach ($resultItem as $offerKey => $offerValue) {

					if ($offerValue["OFFER_ID"] == $offerIdValue["PROPERTIES"][$externalOfferId]["VALUE"]) {

						$tmpPriceId = null;

						$cp = new \CPrice();

						$dbres = $cp->GetList(
							[],
							[
								"PRODUCT_ID" => $offerIdValue["ID"]
							],
							false,
							false,
							["ID"]
						);

						while ($res = $dbres->GetNext()) {
							$tmpPriceId = $res;
						}

					echo "Обновлена цена для торгового предложения {$offerIdValue["ID"]}. ID ценового предложения - "
							. \CPrice::Update(
								$tmpPriceId["ID"],
								[
									"PRODUCT_ID" => $offerIdValue["ID"],
									"PRICE" => $offerValue["PRICE"],
									"CURRENCY" => "RUB"
								]
							);

						echo PHP_EOL;
					}
				}
			}
		}
		echo PHP_EOL;
	}

	// Версия только для skiboard, имеет особенность в виде сезонной наценки
	public static function update__skiboard(array $catalogSkus, array $resultArray)
	{
		foreach ($catalogSkus as $offerIdKey => $offerIdValue) {
			foreach ($resultArray as $resultKey => $resultItem) {
				foreach ($resultItem as $offerKey => $offerValue) {
					// OFFER_ID из XML vs. SKIBOARD_EXTERNAL_OFFER_ID из каталога
					if ($offerValue["OFFER_ID"] === $offerIdValue["PROPERTIES"]["SKIBOARD_EXTERNAL_OFFER_ID"]["VALUE"]) {
						// SEASON_PRICE из XML vs. Цена ТП из каталога
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
