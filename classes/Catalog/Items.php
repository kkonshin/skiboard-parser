<?php

namespace Parser\Catalog;

use \Bitrix\Main\Loader;

// TODO рефактор, сейчас этот паттерн можно использовать только 1 раз, т.к. полученный значения сохраняются в массивы

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

	public function reset()
	{
		$this->list = [];
		$this->itemsIds = [];
		$this->skusList = [];
		$this->skusListFlatten = [];
	}

	/**
	 * Возвращает товары временного раздела. При передаче SECTION_ID => '' вернет товары из всех разделов
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
			"IBLOCK_SECTION_ID"  // Вернет привязку только к разделу с минимальным ID!
		];

		if (count($additionalFilter) > 0) {
			$filter = array_merge($filter, $additionalFilter);
			foreach ($filter as $key => $value){
				if ($value == false){
					unset($filter[$key]);
				}
			}
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
					"NAME",
					"QUANTITY"
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
		$this->addSkuQuantity();
		return $this;
	}

	/**
	 * Служебная функция, добавляет количество единиц ТП в $this->skusListFlatten
	 */

	private function addSkuQuantity()
	{
		$ids = [];
		$quantity = [];

		if (count($this->skusListFlatten)){
			foreach ($this->skusListFlatten as $sku){
				$ids[] = $sku["ID"];
			}

			$dbRes = \CCatalogProduct::GetList([],["ID" => $ids], false, false, ["ID", "QUANTITY"]);

			while($res = $dbRes->GetNext()){
				$quantity[$res["ID"]] = $res["QUANTITY"];
			}
			foreach ($this->skusListFlatten as $key => $value){
				foreach ($quantity as $id => $skuQuantity){
					if ($value["ID"] == $id){
						$this->skusListFlatten[$key]["QUANTITY"] = $skuQuantity;
					}
				}
			}
		}
	}
}
