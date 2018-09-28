<?php

if (!function_exists("setSize")) {

	function setSize($resultArray)
	{

// Получаем массив уникальных значений размеров источника
		$sourceSizesArray = [];

		foreach ($resultArray as $key => $item) {
			foreach ($item as $k => $offer) {
				if (!empty($offer["ATTRIBUTES"]["Размер"])) {
					$sourceSizesArray[] = trim($offer["ATTRIBUTES"]["Размер"]);
				}
			}
		}

		$sourceSizesArray = array_unique($sourceSizesArray);

// Получаем массив существующих значений свойства "SIZE"
		$sizePropArray = [];

		$dbRes = CIBlockProperty::GetPropertyEnum(120,
			[], []
		);

		while ($res = $dbRes->GetNext()) {
			$sizePropArray[] = $res;
		}

		echo "Количество значений свойства 'SIZE' в базе: " . count($sizePropArray) . PHP_EOL;

		$tmpSizeArray = [];
		foreach ($sizePropArray as $key => $value) {
			$tmpSizeArray[] = $value["VALUE"];
		}

		$newSizesArray = null;

		if (is_array($sizePropArray) && !empty($sizePropArray)) {
			$newSizesArray = array_values(array_diff($sourceSizesArray, $tmpSizeArray));
		}

		//Добавим новые значения в свойство "SIZE"

		foreach ($newSizesArray as $key => $sizeValue) {
			if (!in_array($sizeValue, $tmpSizeArray)) {
				$tmpValue = new CIBlockPropertyEnum;
				$tmpValue->Add(['PROPERTY_ID' => 120, 'VALUE' => $sizeValue]);
			}
		}

	// Заново получаем массив всех значений размеров

		$sizePropArray = [];
		$valueIdPairsArray = [];

		$dbRes = CIBlockProperty::GetPropertyEnum(120,
			[], []
		);

		while ($res = $dbRes->GetNext()) {
			$sizePropArray[] = $res;
		}

		foreach ($sizePropArray as $key => $value) {
			$valueIdPairsArray[$value["VALUE"]] = $value["ID"];
		}
	}
	return $valueIdPairsArray;
}