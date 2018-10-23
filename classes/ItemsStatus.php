<?php

namespace Parser;

use \Bitrix\Main\Loader;

class ItemsStatus
{
	/**
	 * Метод создает объект в зависимости от ID инфоблока и ID раздела инфоблока
	 * ItemsStatus constructor.
	 * @param SectionParams $params
	 */
	function __construct(SectionParams $params)

	{
		$this->catalogIblockId = $params->catalogIblockId;
		$this->tempCatalogSection = $params->tempCatalogSection;

		if (!Loader::includeModule('iblock')) {
			die('Не удалось загрузить модуль инфоблоки');
		}

	}

	/**
	 * Метод для получения списка товаров раздела. Возвращает массив, содержащий активность товара и его ID категории
	 * из файла XML
	 * @return array|bool
	 */
	protected function getList()
	{
		$dbRes = \CIBlockElement::GetList(
			[],
			["IBLOCK_ID" => $this->catalogIblockId, "SECTION_ID" => $this->tempCatalogSection],
			false,
			false,
			[
				"IBLOCK_ID",
				"ID",
				"NAME",
				"ACTIVE",
				"PROPERTY_CATEGORY_ID"
			]
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
	 * Вспомогательный метод, возвращает массив ID товаров для отбора связанных ТП
	 * @return array|bool
	 */
	protected function getItemsIds()
	{

		$itemsList = $this->getList();

		if (is_array($itemsList)) {

			$itemsIdsArray = [];

			foreach ($itemsList as $itemKey => $itemValue){
				$itemsIdsArray[] = $itemValue["ID"];
			}
			return $itemsIdsArray;
		}

		return false;

	}

	/**
	 * Возвращает список торговых предложений, связанных с товарами временного раздела
	 * @return array|bool
	 */

	public function getSkuList()
	{
		$itemsIdsArray = $this->getItemsIds();

		if ($itemsIdsArray){
			$skuList = \CCatalogSku::getOffersList($itemsIdsArray, 0, [], ["ID", "IBLOCK_ID", "ACTIVE"]);
			if ($skuList){
				return $skuList;
			}
		}
		return false;
	}
}
