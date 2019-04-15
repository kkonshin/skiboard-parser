<?php

namespace Parser\Utils;

// FIXME этот класс работает только для skiboard - реализовать универсальный

class Price
{

	// TODO сначала получить молные массивы цен для наших ТП, потом установить

	public static function update(Array $catalogSkus, Array $resultArray)
	{

//		file_put_contents(__DIR__ . "/../../logs/console__skusPrices.log", print_r($catalogSkus, true));
//		file_put_contents(__DIR__ . "/../../logs/console__resultArray.log", print_r($resultArray, true));

		$tmpPricesIds = [];

		foreach ($catalogSkus as $offerIdKey => $offerIdValue) {
			foreach ($resultArray as $resultKey => $resultItem) {
				foreach ($resultItem as $offerKey => $offerValue) {

					if ($offerValue["OFFER_ID"] === $offerIdValue["ID"]) {

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

							/*
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
							*/
					}

				}

			}

		}
		file_put_contents(__DIR__ . "/../../logs/console__skusPrices.log", print_r($tmpPricesIds, true));
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
