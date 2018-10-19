<?php

namespace Parser;

class BindToSections extends ItemsStatus
{
	public static function bind(ItemsStatus $object, Array $sections)
	{
		$sectionsArray[] = $object->tempCatalogSection;

		$itemsList = $object->getList();

		foreach ($itemsList as $itemKey => $itemValue) {
			foreach ($sections as $sectionKey => $sectionValuesArray){
				foreach ($sectionValuesArray as $sectionValue){
					if ($itemValue["PROPERTY_CATEGORY_ID_VALUE"] == $sectionKey){
						$sectionsArray[] = $sectionValue;
					}
				}
			}

			\CIBlockElement::SetElementSection(
				$itemValue["ID"],
				$sectionsArray,
				false,
				$object->catalogIblockId
			);
		}
	}
}