<?php

namespace Parser\Utils;

class ExternalOfferId
{
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
	 * @param array $itemsList
	 * @param array $resultArray
	 * @param $propertyName
	 * @param $translitParams
	 */

	// Вариант для gssport.ru
	// Используется только транслитерация поля NAME - добавить цвет
	public static function updateExternalItemId(Array $itemsList, Array $resultArray, $propertyName, $translitParams)
	{
		foreach ($resultArray as $resultKey => $resultValue) {

			$resultItemCode = trim(\CUtil::translit($resultValue[0]["NAME"], "ru", $translitParams));

			$resultItemCodeWithColor = trim(\CUtil::translit($resultValue[0]["NAME"], "ru", $translitParams));

			foreach ($itemsList as $itemKey => $itemValue) {

				$itemsListItemCode = trim(\CUtil::translit($itemValue["NAME"], "ru", $translitParams));

				if ($resultItemCode == $itemsListItemCode) {
					echo "Уникальный ключ {$resultKey} будет присвоен товару {$itemValue['NAME']}" . PHP_EOL;
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

	public function update($elementId, $iblockId, Array $propertyValues = [])
	{
		\CIBlockElement::SetPropertyValuesEx(
			$elementId,
			$iblockId,
			$propertyValues
		);
	}

}
