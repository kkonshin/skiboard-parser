<?php

namespace Parser;

use \Bitrix\Main\Loader;

class ItemsStatus
{
	function __construct(SectionParams $params)

	{
		$this->catalogIblockId = $params->catalogIblockId;
		$this->tempCatalogSection = $params->tempCatalogSection;

		if (!Loader::includeModule('iblock')) {
			die('Не удалось загрузить модуль инфоблоки');
		}

	}

	protected function getList()
	{
		/*
		 * Метод получает список товаров временного раздела
		 */

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

	protected function getItemsIds()
	{
		/*
		 * Вспомогательная функция, получает массив ID товаров для отбора связанных ТП
		 */

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

	public function getSkuList()
	{
		/*
		 * Метод получает список торговых предложений, связанных с товарами временного раздела
		 */

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
