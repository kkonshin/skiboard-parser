<?php

namespace Parser\Utils;

class Activate
{
	/**
	 * @param array $list
	 */
	public static function activateItems(Array $list)
	{
		foreach ($list as $key => $item) {
			$element = new \CIBlockElement();
			$element->Update($item["ID"], ["ACTIVE" => "Y"]);
		}
	}

	/**
	 * @param array $skusListFlatten
	 */
	public static function activateSkus(Array $skusListFlatten)
	{
		foreach ($skusListFlatten as $sku) {
			$element = new \CIBlockElement();
			$element->Update($sku["ID"], ["ACTIVE" => "Y"]);
		}
	}
}