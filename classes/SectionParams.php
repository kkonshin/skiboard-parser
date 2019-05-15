<?php

namespace Parser;

class SectionParams
{
	/**
	 * SectionParams constructor.
	 * @param $catalogIblockId
	 * @param $tempCatalogSection
	 * @param int $skuIblockId
	 */
	function __construct($catalogIblockId, $tempCatalogSection, $skuIblockId = 13)
	{
		$this->catalogIblockId = $catalogIblockId;
		$this->tempCatalogSection = $tempCatalogSection;
		$this->skuIblockId = $skuIblockId;
	}
}