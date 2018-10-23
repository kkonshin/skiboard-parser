<?php

namespace Parser;

use \Bitrix\Main\Mail\Event;

class Mail
{

	// TODO письмо о появлении новых ТОВАРОВ

	/**
	 * Отсылает письмо в отдел продаж. Письмо содержит список новых категорий, при их появлении в прайсе.
	 * @param array $newSectionsList
	 * @return \Bitrix\Main\Entity\AddResult
	 */
	public static function sendMail(Array $newSectionsList)
	{
		$result = Event::send([
			"EVENT_NAME" => "NEW_CATALOG_SECTION_IN_YML",
			"LID" => "s1",
			"C_FIELDS" => [
//				"EMAIL" => "konshin@profi-studio.ru",
				"NEW_SECTIONS_LIST" => $newSectionsList
			]
		]);

		return $result;
	}
}