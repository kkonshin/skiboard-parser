<?php

namespace Parser;

use \Bitrix\Main\Mail\Event;

class Mail
{
	/**
	 * Отсылает письмо в отдел продаж. Письмо содержит список новых категорий, при их появлении в прайсе.
	 * @param array $newSectionsList
	 * @return \Bitrix\Main\Entity\AddResult
	 */
	public static function sendNewSections(Array $newSectionsList)
	{
		$result = Event::send([
			"EVENT_NAME" => "NEW_CATALOG_SECTION_IN_YML",
			"LID" => "s1",
			"C_FIELDS" => [
				"EMAIL" => "konshin@profi-studio.ru",
				"NEW_SECTIONS_LIST" => $newSectionsList
			]
		]);

		return $result;
	}

	public static function sendNewItems($newItemsList)
	{

		$htmlString = "";

		$htmlString .= "<table>";
		$htmlString .= "<thead></thead>";
		$htmlString .= "<tbody>";
		$htmlString .= "<tr>";
		$htmlString .= "<th colspan='2'>Название товара</th>";
		$htmlString .= "<th colspan='2'>Сайт продавца</th>";
		$htmlString .= "<th colspan='2'>Ссылка на товар в каталоге</th>";
		$htmlString .= "</tr>";

		foreach ($newItemsList as $item) {
			$htmlString .=
				"<tr>"
				. "<td colspan='2'>" . $item["NAME"] . "</td>"
				. "<td colspan='2'>" . $item["VENDOR_SITE_NAME"] . "</td>"
				. "<td colspan='2'>" . $item["DETAIL_PAGE_URL"]	. "</td>"
				. "</tr>";
		}

		$htmlString .= "</tbody>";
		$htmlString .= "</table>";

		try {
			$result = Event::send([
				"EVENT_NAME" => "NEW_ITEMS_IN_YML",
				"LID" => "s1",
				"C_FIELDS" => [
					"EMAIL" => "konshin@profi-studio.ru",
					"NEW_ITEMS_LIST" => $htmlString,
				]
			]);
			return $result;
		} catch (\Exception $e) {
			return false;
		}
	}
}