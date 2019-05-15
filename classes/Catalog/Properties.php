<?php

namespace Parser\Catalog;

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
				"IBLOCK_ID" => $propertyParams["IBLOCK_ID"],
				"CHECK_PERMISSIONS" => "N",
				"CODE" => $propertyParams["CODE"]
			]
		);

		if ($res = $catalogIbPropsDb->GetNext()) {
			echo PHP_EOL;
			echo "Свойство {$propertyParams["NAME"]} - {$propertyParams["CODE"]} уже существует";
			echo PHP_EOL;
		} else {
			$arPropertyFields = [
				"NAME" => $propertyParams["NAME"],
				"ACTIVE" => "Y",
				"CODE" => $propertyParams["CODE"],
				"PROPERTY_TYPE" => "S",
				"IBLOCK_ID" => $propertyParams["IBLOCK_ID"],
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
}
