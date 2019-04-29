<?php

namespace Parser\Catalog;

use \Bitrix\Main\Loader;

class Items
{
	/**
	 * Items constructor.
	 * Принимает объект настроек
	 * @param \Parser\SectionParams $params
	 */
	function __construct(\Parser\SectionParams $params)

	{
		$this->catalogIblockId = $params->catalogIblockId;
		$this->tempCatalogSection = $params->tempCatalogSection;

		// Инициализация параметров для шаблона "Chaining"

		$this->list = [];
		$this->itemsIds = [];
		$this->skusList = [];
		$this->skusListFlatten = [];

		if (!Loader::includeModule('iblock')) {
			die('Не удалось загрузить модуль инфоблоки');
		}

	}

	/**
	 * Получает список товаров временного раздела, принимает дополнительные параметры
	 * в зависимости от конкретного парсера
	 * @param array $additionalFilter
	 * @param array $properties
	 * @return $this
	 */

	public function getList($additionalFilter = [], $properties = [])
	{

		$filter = [
			"IBLOCK_ID" => $this->catalogIblockId,
			"SECTION_ID" => $this->tempCatalogSection
		];

		$fields = [
			"IBLOCK_ID",
			"ID",
			"NAME",
			"CODE",
			"ACTIVE",
		];

		if (count($additionalFilter) > 0) {
			$filter = array_merge($filter, $additionalFilter);
		}

		if (count($properties) > 0) {
			$fields = array_merge($fields, $properties);
		}

		$dbRes = \CIBlockElement::GetList(
			[],
			$filter,
			false,
			false,
			$fields
		);

		while ($res = $dbRes->GetNext()) {
			$this->list[] = $res;
		}

		return $this;
	}

	/**
	 * Получает массив ID для массива товаров
	 * @return $this
	 */
	public function getItemsIds()
	{
		if (is_array($this->list)) {
			foreach ($this->list as $itemKey => $itemValue) {
				$this->itemsIds[] = $itemValue["ID"];
			}
		}
		return $this;
	}

	/**
	 * Получает список торговых предложений для списка ID родительских товаров.
	 *
	 * $extraParameters имеет вид ["CODE" => ["WHAT_PARAMETER_WE_WANT"]]
	 *
	 * @param array $extraParameters
	 * @return $this
	 */

	public function getSkusList($extraParameters = [])
	{
		if (count($this->itemsIds) > 0) {
			$this->skusList = \CCatalogSku::getOffersList(
				$this->itemsIds,
				0,
				[],
				[
					"ID",
					"IBLOCK_ID",
					"ACTIVE",
					"NAME"
				],
				$extraParameters
			);
		}
		return $this;
	}

	/**
	 * Уменьшает вложенность массива родительский товар => торговые предложения на 1 уровень
	 * @return $this
	 */

	public function getSkusListFlatten()
	{
		foreach ($this->skusList as $itemKey => $itemValue) {
			foreach ($itemValue as $offerKey => $offerValue) {
				$this->skusListFlatten[$offerKey] = $offerValue;
			}
		}
		return $this;
	}

}
