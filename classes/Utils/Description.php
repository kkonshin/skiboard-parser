<?php

namespace Parser\Utils;

class Description extends \Parser\ItemsStatus
{
	/**
	 * Обновление детального описания товара
	 * @param \Parser\ItemsStatus $object
	 * @param array|null $resultArray
	 */

	public static function updateDescription(\Parser\ItemsStatus $object, array $resultArray=null)
	{
		$itemsList = $object->getList();

		try {
			foreach ($itemsList as $itemKey => $itemValue) {
				foreach ($resultArray as $resultArrayKey => $resultArrayValue){

					// FIXME изменилась сборка названия товара
					// Проверить сборку названия

//					echo \CUtil::translit($resultArrayValue[0]["NAME"] . ' ' . $resultArrayValue[0]["OFFER_ID"], "ru", P_TRANSLIT_PARAMS) . PHP_EOL;
//					echo $itemValue["CODE"] . PHP_EOL;

					if (\CUtil::translit($resultArrayValue[0]["NAME"] . ' ' . $resultArrayValue[0]["OFFER_ID"], "ru", P_TRANSLIT_PARAMS) === $itemValue["CODE"]){
						$element = new \CIBlockElement();
						echo $element->Update($itemValue["ID"], ["DETAIL_TEXT" => html_entity_decode($resultArrayValue[0]["HTML_PARSED_DESCRIPTION"])]);
					} else {
						throw new \Exception("Обновить детальное описание не удалось");
					}
				}
			}
		} catch (\Exception $exception){
			echo $exception->getMessage() . PHP_EOL;
		}
	}
}