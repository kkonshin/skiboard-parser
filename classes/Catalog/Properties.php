<?php

namespace Parser\Catalog;

// TODO рефактор
// Методы должны работать с любыми парсерами
class Properties
{
	/**
	 * Проверяет наличие и (при отсутствии) создает свойство для хранения внешнего ключа товара
	 * @param array $propertyParams
	 */
	public static function createExternalItemIdProperty(Array $propertyParams)
	{
		$catalogIbPropsDb = \CIBlockProperty::GetList(
			[],
			[
				"IBLOCK_ID" => CATALOG_IBLOCK_ID,
				"CHECK_PERMISSIONS" => "N",
				"CODE" => $propertyParams["CODE"] // "P_SKIBOARD_GROUP_ID"
			]
		);

		if ($res = $catalogIbPropsDb->GetNext()) {
			echo PHP_EOL;
			echo "Свойство {$propertyParams["NAME"]} - {$propertyParams["CODE"]} уже существует";
			echo PHP_EOL;
		} else {
			$arPropertyFields = [
				"NAME" => $propertyParams["NAME"], //"Идентификатор товара в каталоге skiboard.ru"
				"ACTIVE" => "Y",
				"CODE" => $propertyParams["CODE"],
				"PROPERTY_TYPE" => "S",
				"IBLOCK_ID" => CATALOG_IBLOCK_ID,
				"SEARCHABLE" => "Y",
				"FILTRABLE" => "Y",
				"VALUES" => [
					0 => [
						"VALUE" => "",
						"DEF" => ""
					]
				]
			];

			$property = new \CIBlockProperty;
			$propertyId = $property->Add($arPropertyFields);

			if ($propertyId > 0) {
				echo PHP_EOL;
				echo "Добавлено свойство инфоблока товаров {$propertyParams["NAME"]} - {$propertyParams["CODE"]}";
				echo PHP_EOL;
			}
		}
	}

	/**
	 * Проверяем и при отсутствии создаем свойство P_KITERU_EXTERNAL_OFFER_ID
	 * Это ключ связывающий торговые предложения XML kite.ru и сохраненные торговые предложения
	 */

	// TODO при необходимости реализовать для skiboard
	/*
	public static function createPKiteruExternalOfferId()
	{
		$catalogIbPropsDb = \CIBlockProperty::GetList(
			[],
			[
				"IBLOCK_ID" => SKU_IBLOCK_ID,
				"CHECK_PERMISSIONS" => "N",
				"CODE" => "P_KITERU_EXTERNAL_OFFER_ID"
			]
		);

		if ($res = $catalogIbPropsDb->GetNext()) {
			$PKiteruExternalOfferId = $res;
		}

		if (empty($PKiteruExternalOfferId)) {
			$arPropertyFields = [
				"NAME" => "Идентификатор торгового предложения в каталоге kite.ru",
				"ACTIVE" => "Y",
				"CODE" => "P_KITERU_EXTERNAL_OFFER_ID",
				"PROPERTY_TYPE" => "S",
				"IBLOCK_ID" => SKU_IBLOCK_ID,
				"SEARCHABLE" => "Y",
				"FILTRABLE" => "Y",
				"VALUES" => [
					0 => [
						"VALUE" => "",
						"DEF" => ""
					]
				]
			];

			$propertyPKiteruExternalOfferId = new \CIBlockProperty;
			$propertyPKiteruExternalOfferId__id = $propertyPKiteruExternalOfferId->Add($arPropertyFields);

			if ($propertyPKiteruExternalOfferId__id > 0) {
				echo PHP_EOL;
				echo "Добавлено свойство инфоблока товаров P_KITERU_EXTERNAL_OFFER_ID";
				echo PHP_EOL;
			}
		} else {
			echo PHP_EOL;
			echo "Свойство {$PKiteruExternalOfferId["NAME"]} - {$PKiteruExternalOfferId["CODE"]} уже существует";
			echo PHP_EOL;
		}
	}
	*/
}