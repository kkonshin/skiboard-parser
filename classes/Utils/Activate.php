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
			$result = $element->Update($item["ID"], ["ACTIVE" => "Y"]);
			if($result){
				echo "Товар {$item["ID"]} активирован" . PHP_EOL;
			}
		}
	}

	/**
	 * @param array $skusListFlatten
	 */
	public static function activateSkus(Array $skusListFlatten)
	{
		foreach ($skusListFlatten as $sku) {
			$element = new \CIBlockElement();
			$result = $element->Update($sku["ID"], ["ACTIVE" => "Y"]);
			if($result){
				echo "Товарное предложение {$sku["ID"]} активировано" . PHP_EOL;
			}
		}
	}
}