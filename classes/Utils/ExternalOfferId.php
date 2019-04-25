<?php

namespace Parser\Utils;

class ExternalOfferId
{

	// TODO проверить для skiboard.ru
	// Возвращает массив ID торговых предложений каталога по их внешним ключам
	public static function getOffersIds(Array $externalKeys, $externalPropertyName)
	{
		$ta = [];
		$dbRes = \CIBlockElement::GetList(
			[],
			[
				"PROPERTY_" . $externalPropertyName => $externalKeys
			],
			false,
			false,
			[
				"IBLOCK_ID",
				"ID",
				"PROPERTY_" . $externalPropertyName
			]
		);
		while ($res = $dbRes->GetNext()) {
			$ta[$res["PROPERTY_" . $externalPropertyName . "_VALUE"]] = $res["ID"];
		}
		return $ta;
	}

	/**
	 * Обновляет (или записывает при отсутствии) внешние ключи товарам каталога
	 * Используется для kite.ru / skiboard.ru
	 * @param array $itemsList
	 * @param array $resultArray
	 * @param $propertyName
	 * @param $translitParams
	 */

	public static function updateExternalItemId(Array $itemsList, Array $resultArray, $propertyName, $translitParams)
	{
		foreach ($resultArray as $resultKey => $resultValue) {
			$resultItemCode = trim(\CUtil::translit($resultValue[0]["NAME"] . ' ' . $resultValue[0]["OFFER_ID"], "ru", $translitParams));
			foreach ($itemsList as $itemKey => $itemValue) {
				if ($resultItemCode == $itemValue["CODE"]) {
					echo $resultKey . ' ' . $resultItemCode . PHP_EOL;
					self::update($itemValue["ID"], CATALOG_IBLOCK_ID, [(string)$propertyName => [$resultKey]]);
				}
			}
		}
	}

	/**
	 * Обновляет значения внешнего ключа торгового предложения.
	 * Первый аргумент - список уже имеющихся ТП.
	 * Второй - результат парсинга XML.
	 * Третьим аргументом принимает название свойства,
	 * в котором хранится внешний ключ ТП
	 * @param array $skuList
	 * @param array $resultArray
	 * @param $propertyName
	 */

	public static function updateExternalOfferId(Array $skuList, Array $resultArray, $propertyName)
	{
		foreach ($resultArray as $resultKey => $resultValue) {
			foreach ($resultValue as $offerKey => $offerValue) {
				foreach ($skuList as $skuKey => $skuValue) {
					if ($skuValue["NAME"] === $offerValue["NAME"] || $skuValue["NAME"] === $offerValue["SHORT_NAME"] . " " . $offerValue["ATTRIBUTES"]["Размер"]) {
//						echo $skuValue["NAME"] . PHP_EOL;
						self::update($skuValue["ID"], SKU_IBLOCK_ID, [(string)$propertyName => [$offerValue["OFFER_ID"]]]);
					}

				}
			}
		}
	}

	/**
	 * Обновляет свойство "ID ТП из прайса skiboard"
	 * @param array $skuList
	 * @param array $resultArray
	 */
	public static function updateExternalOfferId__skiboard(array $skuList, array $resultArray)
	{
		foreach ($resultArray as $resultKey => $resultValue) {
			foreach ($resultValue as $offerKey => $offerValue) {
				foreach ($skuList as $skuKey => $skuValue) {
					if ($skuValue["NAME"] === $offerValue["NAME"] . " " . $offerValue["ATTRIBUTES"]["Размер"] . " " . $offerValue["ATTRIBUTES"]["Артикул"]) {
						echo "Обновляем свойство SKIBOARD_EXTERNAL_OFFER_ID для " . $skuValue["NAME"] . " > OFFER_ID: " . $offerValue["OFFER_ID"] . PHP_EOL;
						self::update($skuValue["ID"], 0, ["SKIBOARD_EXTERNAL_OFFER_ID" => [$offerValue["OFFER_ID"]]]);
					}
				}
			}
		}
	}

	public function update($elementId, $iblockId, Array $propertyValues = [])
	{
		\CIBlockElement::SetPropertyValuesEx(
			$elementId,
			$iblockId,
			$propertyValues
		);
	}

}
