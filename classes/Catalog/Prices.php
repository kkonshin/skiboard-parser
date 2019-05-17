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
	public static function update(Array $catalogSkus, Array $resultArray)
	{
		foreach ($catalogSkus as $offerIdKey => $offerIdValue) {
			foreach ($resultArray as $resultKey => $resultItem) {
				foreach ($resultItem as $offerKey => $offerValue) {
					if ($offerValue["OFFER_ID"] == $offerIdValue["PROPERTIES"]["P_GSSPORT_EXTERNAL_OFFER_ID"]["VALUE"]) {
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

						echo "Обновлена цена ТП {$offerIdValue["ID"]} - {$offerIdValue["NAME"]} - {$offerValue["PRICE"]} руб. ID ценового предложения - "
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
}
