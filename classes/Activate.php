<?php

namespace Parser;

class Activate extends ItemsStatus
{
	public static function activateItems(ItemsStatus $object)
	{
		$temp = $object->getList();
		foreach ($temp as $tempKey => $tempValue) {
			$element = new \CIBlockElement();
			$element->Update($tempValue["ID"], ["ACTIVE" => "Y"]);
		}
	}
}