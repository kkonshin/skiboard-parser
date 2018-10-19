<?php

namespace Parser;

class Deactivate extends ItemsStatus
{
	public static function deactivateItems(ItemsStatus $object)
	{
		/*
		 * Метод деактивирует товары в выбранном разделе по заданным условиям
		 */
		$temp = $object->getList();
		foreach ($temp as $value) {
			$element = new \CIBlockElement();
			$element->Update($value["ID"], ["ACTIVE" => "N"]);
		}
	}
	public static function deactivateSkus(ItemsStatus $object)
	{
		/*
		 * Метод деактивирует торговые предложения, связанные со списком товаров
		 */
		$skuList = $object->getSkuList();

		foreach ( $skuList as $item) {
			foreach ($item as $sku){
				$element = new \CIBlockElement();
				$element->Update($sku["ID"], ["ACTIVE" => "N"]);
			}
		}
	}
}