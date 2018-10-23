<?php

namespace Parser;

class BindToSections extends ItemsStatus
{
	/**
	 * Метод привязывает товары к перечню разделов. Принимает объект ItemsStatus и массив ID разделов для привязки
	 * @param ItemsStatus $object
	 * @param array $sections
	 */
	public static function bind(ItemsStatus $object, Array $sections)
	{
		$sectionsArray = [];

		$itemsList = $object->getList();

		foreach ($itemsList as $itemKey => $itemValue) {
			foreach ($sections as $sectionKey => $sectionValuesArray){
				foreach ($sectionValuesArray as $sectionValue){
					if ($itemValue["PROPERTY_CATEGORY_ID_VALUE"] == $sectionKey){
						$sectionsArray[] = $sectionValue;
					}
				}
			}

			$sectionsArray[] = $object->tempCatalogSection;

			\CIBlockElement::SetElementSection(
				$itemValue["ID"],
				$sectionsArray,
				false,
				$object->catalogIblockId
			);

			unset($sectionsArray);

		}
	}
}