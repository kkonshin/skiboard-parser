<?php

namespace Parser\Utils;

class Description extends \Parser\ItemsStatus
{
	public static function updateDescription(\Parser\Catalog\Items $object, array $resultArray, $useHtmlDescription = false)
	{
		$items = $object->getList()->list;
		try {
			foreach ($items as $itemKey => $itemValue) {
				foreach ($resultArray as $resultArrayKey => $resultArrayValue){
					if (\CUtil::translit($resultArrayValue[0]["NAME"] . ' ' . $resultArrayValue[0]["OFFER_ID"], "ru", P_TRANSLIT_PARAMS) === $itemValue["CODE"]){
						$element = new \CIBlockElement();
						//
						$description = $useHtmlDescription && $resultArrayValue[0]["HTML_PARSED_DESCRIPTION"]
							? $resultArrayValue[0]["HTML_PARSED_DESCRIPTION"]
							: $resultArrayValue[0]["DESCRIPTION"];
						echo $element->Update($itemValue["ID"], ["DETAIL_TEXT" => html_entity_decode($description)]);
					}
				}
			}
		} catch (\Exception $exception){
			echo "Обновить детальное описание не удалось" . PHP_EOL;
			echo $exception->getMessage() . PHP_EOL;
		}
	}
}