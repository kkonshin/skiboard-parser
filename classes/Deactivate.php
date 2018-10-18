<?php

namespace Parser;

class Deactivate extends ItemsStatus
{
	public static function deactivateItems(ItemsStatus $object)
	{
		$temp = $object->getList();
		foreach ($temp as $tempKey => $tempValue) {
			$element = new \CIBlockElement();
			$element->Update($tempValue["ID"], ["ACTIVE" => "N"]);
		}
	}
}