<?php


namespace Parser;

use \Bitrix\Main\Loader;

class PropertyTypeList
{
	/**
	 * Создает объект для работы со свойством инфоблока типа "Список"
	 * PropertyTypeList constructor.
	 * @param $propertyId
	 */
	function __construct($propertyId)
	{
		$this->propertyId = $propertyId;

		if (!Loader::includeModule('iblock')) {
			die('Не удалось загрузить модуль инфоблоки');
		}
	}

	/**
	 * Возвращает массив значений свойства по ID свойства типа "Список"
	 * @return array
	 */
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

	/**
	 * Устанавливает для элемента инфоблока значения свойства типа "Список"
	 * @param $elementId
	 * @param $iblockId
	 * @param array $propertyValues
	 */
	public function setPropertyValues($elementId, $iblockId, Array $propertyValues = [])
	{
		\CIBlockElement::SetPropertyValuesEx(
			$elementId,
			$iblockId,
			$propertyValues
		);
	}
}