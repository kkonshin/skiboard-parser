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
}