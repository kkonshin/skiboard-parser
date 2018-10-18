<?php

namespace Parser;

class SectionParams
{
	function __construct($catalogIblockId, $tempCatalogSection)
	{
		$this->catalogIblockId = $catalogIblockId;
		$this->tempCatalogSection = $tempCatalogSection;
	}
}