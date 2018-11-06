<?php

namespace Parser\Utils;

class ExternalOfferId
{
	public static function updateExternalOfferId(array $skuList)
	{

	}


	public function update($elementId, $iblockId, Array $propertyValues = [])
	{
		\CIBlockElement::SetPropertyValuesEx(
			$elementId,
			$iblockId,
			$propertyValues
		);
	}

}
