<?php

namespace Parser;

class SectionParams
{
	/**
	 * Позволяет создавать экземпляры для конфигурирования парсеров
	 * Инъекция зависимости для Items
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