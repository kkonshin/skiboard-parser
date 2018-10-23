<?php

namespace Parser;

class Deactivate extends ItemsStatus
{
	/**
	 * Метод деактивирует товары временного раздела
	 * @param ItemsStatus $object
	 */
	public static function deactivateItems(ItemsStatus $object)
	{
		$temp = $object->getList();
		foreach ($temp as $value) {
			$element = new \CIBlockElement();
			$element->Update($value["ID"], ["ACTIVE" => "N"]);
		}
	}

	/**
	 * Метод деактивирует торговые предложения, связанные с товарами временного раздела
	 * @param ItemsStatus $object
	 */
	public static function deactivateSkus(ItemsStatus $object)
	{
		$skuList = $object->getSkuList();

		foreach ( $skuList as $item) {
			foreach ($item as $sku){
				$element = new \CIBlockElement();
				$element->Update($sku["ID"], ["ACTIVE" => "N"]);
			}
		}
	}
}