<?php

namespace Parser;

class SectionParams
{
	/**
	 * Инъекция зависимости для ItemsStatus
	 * SectionParams constructor.
	 * @param $catalogIblockId
	 * @param $tempCatalogSection
	 */

	function __construct($catalogIblockId, $tempCatalogSection)
	{
		$this->catalogIblockId = $catalogIblockId;
		$this->tempCatalogSection = $tempCatalogSection;
	}
}