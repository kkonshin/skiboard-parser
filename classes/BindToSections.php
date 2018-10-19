<?php

namespace Parser;

class BindToSections extends ItemsStatus
{
	public static function bind(ItemsStatus $object, Array $sections)
	{
		// ЭТОТ СКРИПТ БЕЗУСЛОВНО ПРИВЯЖЕТ ВСЕ ИМЕЮЩИЕСЯ ТОВАРЫ К УКАЗАННЫМ В МАССИВЕ КАТЕГОРИЯМ
		// Допилить.
		// Возм проверять раздел на наличие?

		$sectionsArray[] = $object->tempCatalogSection; // Добавлять к каждому товару

		$itemsList = $object->getList(); // Список всех товаров раздела

		foreach ($itemsList as $itemKey => $itemValue) {
			foreach ($sections as $sectionKey => $sectionValuesArray){
				foreach ($sectionValuesArray as $sectionValue){
//					echo $sectionValue . PHP_EOL;
//					echo $itemValue["CATEGORY_ID"] . PHP_EOL;
//					print_r($itemValue);
					if ($itemValue["CATEGORY_ID"] == $sectionValue){
						$sectionsArray[] = $sectionValue;
						echo $sectionValue . PHP_EOL;
					}
				}
			}
			// TODO помни что $sections это массив
			\CIBlockElement::SetElementSection(
				$itemValue["ID"],
				$sectionsArray,
				false,
				$object->catalogIblockId
			);
		}
	}
}