<?php


namespace Parser;

use \Bitrix\Main\Loader;

class PropertyTypeList
{
	function __construct($propertyId)
	{
		$this->propertyId = $propertyId;

		if (!Loader::includeModule('iblock')) {
			die('Не удалось загрузить модуль инфоблоки');
		}
	}

	public function getProperty()
	{
		$propertyArray = [];

		$dbRes = \CIBlockProperty::GetPropertyEnum($this->propertyId,
			[], []
		);

		while ($res = $dbRes->GetNext()) {
			$propertyArray[] = $res;
		}

		return $propertyArray;
	}

	public function setPropertyValues($elementId, $iblockId, Array $propertyValues = [])
	{
		\CIBlockElement::SetPropertyValuesEx(
			$elementId,
			$iblockId,
			$propertyValues
		);
	}
}