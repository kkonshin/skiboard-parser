<?php
//-----------------------------------------СОХРАНЕНИЕ (ADD) ЭЛЕМЕНТОВ (ПРОТОТИП)--------------------------------------//

// Ограничение длины массива для разработки
$offset = 0;
$length = count($resultArray) - $offset;
$length = 5;
$resultArray = array_slice($resultArray, $offset, $length, true);

//file_put_contents(__DIR__ . "/logs/result.log", print_r($resultArray, true));

echo "Количество товаров для записи: " . count($resultArray) . "\n";

$counter = 0;

$arCatalog = CCatalog::GetByID(SKU_IBLOCK_ID); // Инфоблок товаров

$IBlockCatalogId = $arCatalog['PRODUCT_IBLOCK_ID']; // ID инфоблока товаров

$SKUPropertyId = $arCatalog['SKU_PROPERTY_ID']; // ID свойства в инфоблоке предложений типа "Привязка к товарам (SKU)"


foreach ($resultArray as $key => $item) {
	try {
		$offerPrice = 0;

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

			if (!empty($offer["CATEGORY_ID"])){
				switch ($offer["CATEGORY_ID"]){

					/**
					 *  Устанавливаем свойство "ТИП", если товар принадлежит к определенной категории
					 */

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

					/**
					 *  Устанавливаем свойство "НАЗНАЧЕНИЕ", если товар принадлежит к определенной категории
					 */

					case 400:
						$itemPurposeId = 1132;
						break;
					case 414:
						$itemTypeId = 1133;
						break;
					case (415 || 381 || 370):
						$itemTypeId = 1134;
						break;
					case (283 || 366 || 368):
						$itemTypeId = 1135;
						break;
				}
			}

		}

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
			"ACTIVE" => "N",
			"DETAIL_PICTURE" => (isset($item[0]["PICTURES"][0])) ? CFile::MakeFileArray($item[0]["PICTURES"][0]) : "",
			"PROPERTY_VALUES" => [
				"SITE_NAME" => "skiboard.ru",
				"GROUP_ID" => $key,
				"CATEGORY_ID" => $item[0]["CATEGORY_ID"],
				"MORE_PHOTO" => (!empty($item[0]["MORE_PHOTO"])) ? $item[0]["MORE_PHOTO"] : "",
				"SKIBOARD_ITEM_TYPE" => isset($itemTypeId) ? $itemTypeId : '',
				"SKIBOARD_ITEM_PURPOSE" => isset($itemPurposeId) ? $itemPurposeId : ''
			]
		];

		if ($productId = $obElement->Add($itemFieldsArray)) {
			echo "Добавлен товар " . $productId . "\n";
		} else {
			echo "Ошибка добавления товара: " . $obElement->LAST_ERROR . "\n";
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

				$obElement = new CIBlockElement();

				// Цена торгового предложения в зависимости от сезона

				if (in_array((int)$offer["CATEGORY_ID"], SUMMER)) {
					$offerPrice = $offer["PRICE"] * 1.5;
				}

				if (in_array((int)$offer["CATEGORY_ID"], WINTER)) {
					$offerPrice = $offer["PRICE"] * 1.6;
				}

				$arOfferProps = [
					$SKUPropertyId => $productId,
					'SIZE' => $valueIdPairsArray[$offer['ATTRIBUTES']['Размер']],
					'EXTERNAL_OFFER_ID' => $offer['OFFER_ID']
				];

				foreach ($offer['ATTRIBUTES'] as $propertyName => $propertyValue) {
					$arOfferProps[strtoupper(CUtil::translit($propertyName, 'ru', $translitParams))] = $propertyValue;
				}

				// TODO проверить отображение детального описания, т.к. приходит htmlescape

				$arOfferFields = [
					'NAME' => $offer["NAME"] . " " . $offer["ATTRIBUTES"]["Размер"] . " " . $offer["ATTRIBUTES"]["Артикул"],
					'IBLOCK_ID' => SKU_IBLOCK_ID,
					'ACTIVE' => 'N',
					"DETAIL_TEXT" => (!empty ($offer["DESCRIPTION"])) ? $offer["DESCRIPTION"] : "",
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
					if ($catalogProductAddResult && !CPrice::SetBasePrice($offerId, $offerPrice, "RUB")) {
						throw new Exception("Ошибка установки цены торгового предложения \"{$offerId}\"");
					}

					$counter++;

					echo "Добавлено торговое предложение " . $offerId . PHP_EOL;

				}
			}
		}
	} catch (Exception $e) {
		echo $e->getMessage() . PHP_EOL;
	}
}

//--------------------------------------КОНЕЦ СОХРАНЕНИЯ (ADD) ЭЛЕМЕНТОВ----------------------------------------------//