<?php

if (!function_exists("setProperties")){
	function setProperties ($resultArray){

		global $translitParams;

		$allSkuPropertiesArray = []; // Все свойства торговых предложений, уже существующие в инфоблоке ТП
		$allSourcePropertiesArray = []; // Все свойства торговых предложений из прайса
		$allSkuPropertiesCodesArray = []; // Массив символьных кодов ТП для проверки уникальности

		$propsResDb = CIBlockProperty::GetList([], ["IBLOCK_ID" => SKU_IBLOCK_ID, "CHECK_PERMISSIONS" => "N"]);
		while ($res = $propsResDb->GetNext()) {
			$allSkuPropertiesArray[] = $res;
		}

		foreach ($resultArray as $key => $item) {
			foreach ($item as $k => $offer) {
				foreach ($offer["ATTRIBUTES"] as $attribute => $attributeValue) {
					if (!in_array($attribute, $allSourcePropertiesArray)) {
						$allSourcePropertiesArray[] = $attribute;
					}
				}
			}
		}

// Сохраним свойства в ИБ ТП, если их там еще нет

		foreach ($allSkuPropertiesArray as $key => $property) {
			$allSkuPropertiesCodesArray[] = $property["CODE"];
		}

		foreach ($allSourcePropertiesArray as $key => $value) {

			$arPropertyFields = [
				"NAME" => $value,
				"ACTIVE" => "Y",
				"CODE" => strtoupper(CUtil::translit($value, "ru", $translitParams)),
				"PROPERTY_TYPE" => "S",
				"IBLOCK_ID" => SKU_IBLOCK_ID,
				"SEARCHABLE" => "Y",
				"FILTRABLE" => "Y",
				"VALUES" => [
					0 => [
						"VALUE" => "",
						"DEF" => "Y"
					]
				]
			];

			if (!in_array($arPropertyFields["CODE"], $allSkuPropertiesCodesArray)) {
				if ($arPropertyFields["CODE"] !== "BREND") {
					$newProperty = new CIBlockProperty;
					$newPropertyId = $newProperty->Add($arPropertyFields);

					if ($newPropertyId > 0) {
						echo "Свойство торговых предложений ID = {$newPropertyId} успешно добавлено \n";
					}
				} else {
					echo "Свойство с символьным кодом {$arPropertyFields['CODE']} уже существует или исключено из записи\n";
				}
			}
		}

	}
}