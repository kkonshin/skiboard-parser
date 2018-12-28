<?php

namespace Parser;

class Activate extends ItemsStatus
{
	/**
	 * @param ItemsStatus $object
	 *
	 */
	public static function activateItems(ItemsStatus $object)
	{
		$temp = $object->getList();
		foreach ($temp as $tempKey => $tempValue) {
			$element = new \CIBlockElement();
			$element->Update($tempValue["ID"], ["ACTIVE" => "Y"]);
		}
	}

	/**
	 * @param ItemsStatus $object
	 */

	public static function activateSkus(ItemsStatus $object)
	{
		/*
		 * Метод активирует торговые предложения, связанные со списком товаров
		 */
		$skuList = $object->getSkuList();

		foreach ($skuList as $item) {
			foreach ($item as $sku) {
				$element = new \CIBlockElement();
				$element->Update($sku["ID"], ["ACTIVE" => "Y"]);
			}
		}
	}
}