<?php

namespace Parser;

class BindToSections extends ItemsStatus
{
	public static function bind(ItemsStatus $object, Array $sections)
	{
		// TODO привязка к временному каталогу тоже должна сохраняться
		// ЭТОТ СКРИПТ БЕЗУСЛОВНО ПРИВЯЖЕТ ВСЕ ИМЕЮЩИЕСЯ ТОВАРЫ К УКАЗАННЫМ В МАССИВЕ КАТЕГОРИЯМ
		// Допилить.
		// Возм проверять раздел на наличие?

		$sections[] = $object->tempCatalogSection;

		$temp = $object->getList();

		foreach ($temp as $tempKey => $tempValue) {
			\CIBlockElement::SetElementSection(
				$tempValue["ID"],
				$sections,
				false,
				$object->catalogIblockId
			);

		}
	}
}