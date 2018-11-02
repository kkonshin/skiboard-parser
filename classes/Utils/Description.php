<?php

namespace Parser\Utils;

class Description extends \Parser\ItemsStatus
{
	public static function updateDescription(\Parser\ItemsStatus $object, array $resultArray=null)
	{
		$itemsList = $object->getList();

		try {
			foreach ($itemsList as $itemKey => $itemValue) {
				foreach ($resultArray as $resultArrayKey => $resultArrayValue){
					if (\CUtil::translit($resultArrayValue[0]["NAME"] . ' ' . $resultArrayValue[0]["OFFER_ID"], "ru", P_TRANSLIT_PARAMS) === $itemValue["CODE"]){
//						echo $resultArrayValue[0]["DESCRIPTION"] . PHP_EOL;
						$element = new \CIBlockElement();
						echo $element->Update($itemValue["ID"], ["DETAIL_TEXT" => html_entity_decode($resultArrayValue[0]["DESCRIPTION"])]);
					}
				}
			}
		} catch (\Exception $exception){
			echo "Обновить детальное описание не удалось" . PHP_EOL;
			echo $exception->getMessage() . PHP_EOL;
		}

	}
}