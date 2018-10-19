<?php

namespace Parser;

class BindToSections extends ItemsStatus
{
	public static function bind(ItemsStatus $object, Array $sections)
	{
		// ЭТОТ СКРИПТ БЕЗУСЛОВНО ПРИВЯЖЕТ ВСЕ ИМЕЮЩИЕСЯ ТОВАРЫ К УКАЗАННЫМ В МАССИВЕ КАТЕГОРИЯМ
		// Допилить.
		// Возм проверять раздел на наличие?

		$sections[] = $object->tempCatalogSection;

		$itemsList = $object->getList(); // Список всех товаров раздела

		foreach ($itemsList as $itemKey => $itemValue) {

			\CIBlockElement::SetElementSection(
				$itemValue["ID"],
				$sections,
				false,
				$object->catalogIblockId
			);

		}
	}
}