<?php

namespace Parser\Utils;

// Требуется рефактор

class ExternalOfferId
{

	// Проверка на связь ТП работает только для kite.ru
	// Для skiboard см ниже
	// Для gssport не реализовано


	// Возвращает массив ID торговых предложений каталога по их внешним ключам

	// FIXME баг в парсере [EXTRA][AVAILABLE_CATEGORIES] не должен содержать поле AVAILABLE -> не передавать пустые поля


	public static function getOffersIds(Array $externalKeys, $externalPropertyName){
		$ta = [];
		$dbRes = \CIBlockElement::GetList(
			[],
			[
				"PROPERTY_".$externalPropertyName => $externalKeys
			],
			false,
			false,
			[
				"IBLOCK_ID",
				"ID",
				"PROPERTY_".$externalPropertyName
			]
		);
		while ($res = $dbRes->GetNext()){
			$ta[$res["PROPERTY_".$externalPropertyName."_VALUE"]]= $res["ID"];
		}
		return $ta;
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
		foreach ($resultArray as $resultKey => $resultValue){
			foreach ($resultValue as $offerKey => $offerValue){
				foreach ($skuList as $skuKey => $skuValue){
					if ($skuValue["NAME"] === $offerValue["NAME"] || $skuValue["NAME"] === $offerValue["SHORT_NAME"] . " " . $offerValue["ATTRIBUTES"]["Размер"]){
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

	/**
	 * Обновляет свойство "ID ТП из прайса skiboard"
	 * @param array $skuList
	 * @param array $resultArray
	 */

	public static function updateExternalOfferId__skiboard(array $skuList, array $resultArray)
	{
		foreach ($resultArray as $resultKey => $resultValue){
			foreach ($resultValue as $offerKey => $offerValue){
				foreach ($skuList as $skuKey => $skuValue){
					if ($skuValue["NAME"] === $offerValue["NAME"] . " " . $offerValue["ATTRIBUTES"]["Размер"] . " " . $offerValue["ATTRIBUTES"]["Артикул"]){
						self::update($skuValue["ID"], 0, ["SKIBOARD_EXTERNAL_OFFER_ID" => [$offerValue["OFFER_ID"]]]);
					}
				}
			}
		}
	}





}
