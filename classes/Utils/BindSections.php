<?php

namespace Parser\Utils;

// TODO проверить работу. Реализовать обобщенный вариант

class BindSections
{
	/**
	 * TODO рефактор, сейчас зависит от таблицы разнесения по разделам только для SKIBOARD
	 * @param array $itemsList
	 * @param array $sections
	 * @param $tempCatalogSection
	 * @param $catalogIblockId
	 */
	public static function bind(Array $itemsList, Array $sections, $tempCatalogSection, $catalogIblockId)
	{
		// Собираем массив разделов для каждого товара
		$sectionsArray = [];

		foreach ($itemsList as $itemKey => $itemValue) {
			foreach ($sections as $sectionKey => $sectionValuesArray) {
				foreach ($sectionValuesArray as $sectionValue) {
					if ($itemValue["PROPERTY_CATEGORY_ID_VALUE"] == $sectionKey) {
						$sectionsArray[] = $sectionValue;
					}
				}
			}

			// Всегда сохраняем привязку ко временному разделу
			$sectionsArray[] = $tempCatalogSection;

			\CIBlockElement::SetElementSection(
				$itemValue["ID"],
				$sectionsArray,
				false,
				$catalogIblockId
			);
			unset($sectionsArray);
		}
	}
}
