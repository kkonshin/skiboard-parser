<?php

namespace Parser;

use \Bitrix\Main\Loader;

class ItemsStatus
{
	function __construct(SectionParams $params)
	{
		$this->catalogIblockId = $params->catalogIblockId;
		$this->tempCatalogSection = $params->tempCatalogSection;
	}

	protected function getList(){

		if (!Loader::includeModule('iblock')) {
			die('Не удалось загрузить модуль инфоблоки');
		}

		$dbRes = \CIBlockElement::GetList(
			[],
			["IBLOCK_ID" => $this->catalogIblockId, "SECTION_ID" => $this->tempCatalogSection],
			false,
			false,
			["IBLOCK_ID", "ID", "NAME", "ACTIVE"]
		);

		while ($res = $dbRes->GetNext()) {
			$itemsList[] = $res;
		}

		if (!empty($itemsList)){
			return $itemsList;
		}
		return false;
	}
}
