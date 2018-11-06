<?php

namespace Parser\Utils;

class ExternalOfferId
{

	/**
	 * Обновляет свойство "ID ТП из прайса skiboard"
	 * @param array $skuList
	 * @param array $resultArray
	 */
	public static function updateExternalOfferId(array $skuList, array $resultArray)
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


	public function update($elementId, $iblockId, Array $propertyValues = [])
	{
		\CIBlockElement::SetPropertyValuesEx(
			$elementId,
			$iblockId,
			$propertyValues
		);
	}

}
