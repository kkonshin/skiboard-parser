<?php

global $USER;
global $addArray;
global $serverName;
global $valueIdPairsArray;

// Ограничение длины массива для разработки
//$offset = 0;
//$length = count($addArray) - $offset;
//$length = 20;
//$addArray = array_slice($addArray, $offset, $length, true);

//file_put_contents(__DIR__ . "/logs/resultArray.log", print_r($addArray, true));

echo "Количество товаров для записи: " . count($addArray) . PHP_EOL;
// Инфоблок товаров
$arCatalog = CCatalog::GetByID(SKU_IBLOCK_ID);
// ID инфоблока товаров
$IBlockCatalogId = $arCatalog['PRODUCT_IBLOCK_ID'];
// ID свойства в инфоблоке предложений типа "Привязка к товарам (SKU)"
$SKUPropertyId = $arCatalog['SKU_PROPERTY_ID'];
// Массив внешних ключей торговых предложений. Используется для избежания записи дублей.
$externalIdsArray = [];

foreach ($catalogSkus as $key => $sku) {
	if (!empty($sku["PROPERTIES"]["SKIBOARD_EXTERNAL_OFFER_ID"]["VALUE"])) {
		$externalIdsArray[$key] = $sku["PROPERTIES"]["SKIBOARD_EXTERNAL_OFFER_ID"]["VALUE"];
	}
}

foreach ($addArray as $key => $item) {
	try {

		$offerPrice = 0;
		$itemTypeId = 0;
		$itemPurposeId = 0;
		$morePhotoArray = []; // Массив дополнительных картинок товара
		$obElement = new CIBlockElement;

		foreach ($item as $itemId => $offer) {
			if (count($offer["PICTURES"]) > 1) {
				foreach ($offer["PICTURES"] as $pictureId => $picture) {
					$tempPicture = CFile::MakeFileArray($picture);
					if (strlen($err = CFile::CheckImageFile($tempPicture)) > 0) {
						$pictureErrorsArray[] = $err;
						continue;
					} else {
						$item[$itemId]["MORE_PHOTO"][$pictureId] = $tempPicture;
					}
				}
			}
			// Только для skiboard.ru
			if (!empty($offer["CATEGORY_ID"])) {
				switch ($offer["CATEGORY_ID"]) {
					// Устанавливаем свойство "ТИП", если товар принадлежит к определенной категории
					case 358:
						$itemTypeId = 1126;
						break;
					case 360:
						$itemTypeId = 1127;
						break;
					case 292:
						$itemTypeId = 1128;
						break;
					case 401:
						$itemTypeId = 1129;
						break;
					case 279:
						$itemTypeId = 1130;
						break;
					case 282:
						$itemTypeId = 1131;
						break;

					// Устанавливаем свойство "НАЗНАЧЕНИЕ", если товар принадлежит к определенной категории
					case 400:
						$itemPurposeId = 1132;
						break;
					case 414:
						$itemTypeId = 1133;
						break;
					case 415:
					case 381:
					case 370:
						$itemTypeId = 1134;
						break;
					case 283:
					case 366:
					case 368:
						$itemTypeId = 1135;
						break;
				}
			}
		}

		// Для отладки
//		if ($itemTypeId > 0) {
//			echo "ID типа товара: " . $itemTypeId . PHP_EOL;
//		}
//		if ($itemPurposeId > 0) {
//			echo "ID назначения товара: " . $itemPurposeId . PHP_EOL;
//		}

		// Лог ошибок изображений
		if (!empty($pictureErrorsArray)) {
			file_put_contents(__DIR__ . "/logs/picture_errors.log", print_r($pictureErrorsArray, true));
		}

		$itemFieldsArray = [
			"MODIFIED_BY" => $USER->GetID(),
			"IBLOCK_ID" => $IBlockCatalogId,
			"IBLOCK_SECTION_ID" => TEMP_CATALOG_SECTION,
			"NAME" => $item[0]["NAME"],
			"CODE" => CUtil::translit($item[0]["NAME"] . ' ' . $item[0]["OFFER_ID"], "ru", $translitParams),
			"ACTIVE" => "Y",
			"DETAIL_PICTURE" => (isset($item[0]["PICTURES"][0])) ? CFile::MakeFileArray($item[0]["PICTURES"][0]) : "",
			"DETAIL_TEXT" => (!empty ($item[0]["DESCRIPTION"])) ? html_entity_decode($item[0]["DESCRIPTION"]) : "",
			"PROPERTY_VALUES" => [
				"SITE_NAME" => "skiboard.ru",
				"P_SKIBOARD_GROUP_ID" => $key,
				"CATEGORY_ID" => $item[0]["CATEGORY_ID"],
				"MORE_PHOTO" => (!empty($item[0]["MORE_PHOTO"])) ? $item[0]["MORE_PHOTO"] : "",
				"SKIBOARD_ITEM_TYPE" => $itemTypeId > 0 ? $itemTypeId : '',
				"SKIBOARD_ITEM_PURPOSE" => $itemPurposeId > 0 ? $itemPurposeId : ''
			]
		];

		if ($productId = $obElement->Add($itemFieldsArray)) {

			echo "Добавлен товар " . $productId . PHP_EOL;

			$filter = [
				"ID" => $productId
			];

			$fields = [
				"DETAIL_PAGE_URL"
			];

			// Собираем массив добавленных товаров для дальнейшей отправки уведомления
			$newItems[$productId]["NAME"] = $itemFieldsArray["NAME"];
			$newItems[$productId]["VENDOR_SITE_NAME"] = $itemFieldsArray["PROPERTY_VALUES"]["SITE_NAME"];
			// Ссылка на детальную страницу ведет во временный раздел
			$newItems[$productId]["DETAIL_PAGE_URL"] = "https://" . $serverName . $items->getList($filter, $fields)->list[0]["DETAIL_PAGE_URL"];

			$items->reset();

		} else {

			echo $obElement->LAST_ERROR
				. ' '
				. $itemFieldsArray["NAME"]
				. ' >Внешний ключ> '
				. $itemFieldsArray["PROPERTY_VALUES"]["P_SKIBOARD_GROUP_ID"]
				. PHP_EOL;
			continue;

		}

		if ($productId) {
			$manXmlId = (!empty($manValueIdPairsArray[strtoupper($item[0]["ATTRIBUTES"]["Бренд"])]))
				? ($manValueIdPairsArray[strtoupper($item[0]["ATTRIBUTES"]["Бренд"])])
				: ($manValueIdPairsArray[$item[0]["ATTRIBUTES"]["Бренд"]]);

			// Запись значения свойства "Производитель". Передается UF_XML_ID из хайлоад-блока
			if (!empty ($manXmlId)) {
				CIBlockElement::SetPropertyValuesEx($productId, $IBlockCatalogId, array("MANUFACTURER" => $manXmlId));
			}

			foreach ($item as $k => $offer) {

			    // TODO проверить

				// Если ТП с таким ключом уже существует в разделе - не записываем
				if (in_array($offer["OFFER_ID"], $externalIdsArray)) {
					continue;
				}
				// FIXME массив существующих ТП выбирается в console.php. Достаточно ли однократной выборки?

				$obElement = new CIBlockElement();
				$arOfferProps = [
					// Привязка к родительскому товару
					$SKUPropertyId => $productId,
					'SIZE' => $valueIdPairsArray[$offer['ATTRIBUTES']['Размер']],
					'SKIBOARD_EXTERNAL_OFFER_ID' => $offer['OFFER_ID']
				];

				foreach ($offer['ATTRIBUTES'] as $propertyName => $propertyValue) {
					$arOfferProps[strtoupper(CUtil::translit($propertyName, 'ru', $translitParams))] = $propertyValue;
				}

				$arOfferFields = [
					'NAME' => $offer["NAME"] . " " . $offer["ATTRIBUTES"]["Размер"] . " " . $offer["ATTRIBUTES"]["Артикул"],
					'IBLOCK_ID' => SKU_IBLOCK_ID,
					'ACTIVE' => 'Y',
					"DETAIL_PICTURE" => (isset($offer["PICTURES"][0])) ? CFile::MakeFileArray($offer["PICTURES"][0]) : "",
					'PROPERTY_VALUES' => $arOfferProps
				];

				// Получаем ID торгового предложения
				$offerId = $obElement->Add($arOfferFields);

				if ($offerId) {
					// Добавляем элемент как товар каталога
					$catalogProductAddResult = CCatalogProduct::Add([
						"ID" => $offerId,
						'QUANTITY' => '5',
						"VAT_INCLUDED" => "Y"
					]);

					if (!$catalogProductAddResult) {
						throw new Exception("Ошибка добавление полей торгового предложения \"{$offerId}\"");
					}

					// и установим цену
					if ($catalogProductAddResult && !CPrice::SetBasePrice($offerId, $offer["SEASON_PRICE"], "RUB")) {
						throw new Exception("Ошибка установки цены торгового предложения \"{$offerId}\"");
					}

					echo "Добавлено торговое предложение " . $offerId . PHP_EOL;

				}
			}
		}

	} catch (Exception $e) {
		echo $e->getMessage() . PHP_EOL;
	}
}
