<?php

namespace Parser\Catalog;
class Properties
{
	/**
	 * Проверяем и при отсутствии создаем свойство P_GROUP_ID
	 * Это ключ связывающий родительские товары XML kite.ru и сохраненные товары
	 */
	public static function createPGroupId()
	{
		$catalogIbPropsDb = \CIBlockProperty::GetList(
			[],
			[
				"IBLOCK_ID" => CATALOG_IBLOCK_ID,
				"CHECK_PERMISSIONS" => "N",
				"CODE" => "P_GROUP_ID"
			]
		);

		if($res=$catalogIbPropsDb->GetNext()){
			$pGroupId = $res;
		}

		if(empty($pGroupId)){
			$arPropertyFields = [
				"NAME" => "Идентификатор товара в каталоге kite.ru",
				"ACTIVE" => "Y",
				"CODE" => "P_GROUP_ID",
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

			$propertyPGroupId = new \CIBlockProperty;
			$propertyPGroupId__id = $propertyPGroupId ->Add($arPropertyFields);

			if ($propertyPGroupId__id > 0) {
				echo "Добавлено свойство инфоблока товаров P_GROUP_ID" . PHP_EOL;
			}
		} else {
			echo "Свойство {$pGroupId["NAME"]} - {$pGroupId["CODE"]} уже существует" . PHP_EOL;
		}
	}

	/**
	 * Проверяем и при отсутствии создаем свойство P_KITERU_EXTERNAL_OFFER_ID
	 * Это ключ связывающий торговые предложения XML kite.ru и сохраненные торговые предложения
	 */

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

		if($res=$catalogIbPropsDb->GetNext()){
			$PKiteruExternalOfferId = $res;
		}

		if(empty($PKiteruExternalOfferId)){
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
			$propertyPKiteruExternalOfferId__id = $propertyPKiteruExternalOfferId ->Add($arPropertyFields);

			if ($propertyPKiteruExternalOfferId__id > 0) {
				echo "Добавлено свойство инфоблока товаров P_KITERU_EXTERNAL_OFFER_ID" . PHP_EOL;
			}
		} else {
			echo "Свойство {$PKiteruExternalOfferId["NAME"]} - {$PKiteruExternalOfferId["CODE"]} уже существует" . PHP_EOL;
		}
	}
}