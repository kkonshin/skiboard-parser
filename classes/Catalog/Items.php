<?php

namespace Parser\Catalog;

use \Bitrix\Main\Loader;

// FIXME Неудачное название класса - сделать рефактор. >> \Catalog\Items

// FIXME нужен ли вообще экземпляр?

class Items
{
	/**
	 * Метод создает объект в зависимости от ID инфоблока и ID раздела инфоблока
	 * ItemsStatus constructor.
	 * @param SectionParams $params
	 */
	function __construct(\Parser\SectionParams $params)

	{
		$this->catalogIblockId = $params->catalogIblockId;
		$this->tempCatalogSection = $params->tempCatalogSection;

		if (!Loader::includeModule('iblock')) {
			die('Не удалось загрузить модуль инфоблоки');
		}

	}

	/**
	 * Получает список элементов инфоблока с базовыми установками + дополнительный фильтр и свойства
	 * @param $additionalFilter
	 * @param array $properties
	 * @return array|bool
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
			"PROPERTY_CATEGORY_ID"
		];

		if (count($additionalFilter) > 0){
			$filter = array_merge($filter, $additionalFilter);
		}

		if (count($properties) > 0){
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
			$itemsList[] = $res;
		}

		if (!empty($itemsList)) {
			return $itemsList;
		}
		return false;
	}

	/**
	 * Возвращает массив ID родительских товаров
	 * @param array $itemsList
	 * @return array|bool
	 */

	public function getItemsIds(Array $itemsList)
	{

//		$itemsList = $this->getList();

		if (is_array($itemsList)) {

			$itemsIdsArray = [];

			foreach ($itemsList as $itemKey => $itemValue) {
				$itemsIdsArray[] = $itemValue["ID"];
			}
			return $itemsIdsArray;
		}

		return false;

	}

	/**
	 * Возвращает список ТП для списка родительских товаров
	 * @param array $itemsIds
	 * @param array $extraParameters
	 * @return array|bool
	 */
	public function getSkuList(Array $itemsIds, $extraParameters = [])
	{
		// FIXME Для skiboard. Рассмотреть необходимость для других парсеров
		$extraParameters = ["CODE" => ["SKIBOARD_EXTERNAL_OFFER_ID"]];

		if (count($itemsIds) > 0) {
			$skuList = \CCatalogSku::getOffersList(
				$itemsIds,
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
			if ($skuList) {
				return $skuList;
			}
		}
		return false;
	}

	/**
	 * Возвращает массив ТП раздела без привязки к товарам, т.е. удаляет уровень вложенности,
	 * содержащий ID родительского товара
	 * @return array
	 */

	public function getSkuListWithoutParent(Array $skuList)
	{
		$skuListWithoutParent = [];

		foreach ($skuList as $itemKey => $itemValue) {
			foreach ($itemValue as $offerKey => $offerValue){
				$skuListWithoutParent[$offerKey] = $offerValue;
			}
		}

		return $skuListWithoutParent;

	}
}
