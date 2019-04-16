<?php

use Parser\HtmlParser\HtmlParser;
use voku\helper\HtmlDomParser;

//-----------------------------------------СОХРАНЕНИЕ (ADD) ЭЛЕМЕНТОВ (ПРОТОТИП)--------------------------------------//

// Ограничение длины массива для разработки
//$offset = 0;
//$length = count($resultArray) - $offset;
//$length = 30;
//$resultArray = array_slice($resultArray, $offset, $length, true);

$linksArray = []; // Для разработки, массив ссылков в описании товара, подлежащих замене

//file_put_contents(__DIR__ . "/logs/resultArray.log", print_r($resultArray, true));

echo "Количество товаров для записи: " . count($resultArray) . "\n";

$arCatalog = CCatalog::GetByID(SKU_IBLOCK_ID); // Инфоблок товаров

$IBlockCatalogId = $arCatalog['PRODUCT_IBLOCK_ID']; // ID инфоблока товаров

$SKUPropertyId = $arCatalog['SKU_PROPERTY_ID']; // ID свойства в инфоблоке предложений типа "Привязка к товарам (SKU)"

foreach ($resultArray as $key => $item) {
	try {
		$offerPrice = 0;

		$itemTypeId = 0;

		$itemPurposeId = 0;

		$morePhotoArray = []; // Массив дополнительных картинок товара

		$obElement = new CIBlockElement;

		// MORE_PHOTO из Html - парсера

		foreach ($item as $itemId => $offer) {
			if (count($offer["HTML_MORE_PHOTO"]) > 1) {
				foreach ($offer["HTML_MORE_PHOTO"] as $pictureId => $picture) {
					$tempPicture = CFile::MakeFileArray($picture);
					if (strlen($err = CFile::CheckImageFile($tempPicture)) > 0) {
						$pictureErrorsArray[] = $err;
						continue;
					} else {
						$item[$itemId]["MORE_PHOTO"][$pictureId] = $tempPicture;
					}
				}
			}
		}

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

		$itemName = (!empty($item[0]["SHORT_NAME"])) ? $item[0]["SHORT_NAME"] : $item[0]["NAME"];
		$itemName = preg_replace( "/\r|\n/", "", $itemName); // удалим из названия товара переносы строк

		// Обработка детального описания товара

		// TODO совместить с реализацией в классе Description

		foreach ($item[0]["HTML_PARSED_DESCRIPTION"]["IMAGES"] as $descriptionImageKey => $descriptionImage) {
			$item[0]["HTML_PARSED_DESCRIPTION"]["SAVED_IMAGES"][$descriptionImageKey] = CFile::GetPath(CFile::SaveFile(CFile::MakeFileArray($descriptionImage), 'item_description'));
//			$resultArray[$key][0]["HTML_PARSED_DESCRIPTION"]["SAVED_IMAGES"][$descriptionImageKey] = $item[0]["HTML_PARSED_DESCRIPTION"]["SAVED_IMAGES"][$descriptionImageKey];
//			echo $resultArray[$key][0]["HTML_PARSED_DESCRIPTION"]["SAVED_IMAGES"][$descriptionImageKey] . PHP_EOL;
		}

//		if (!empty($item[0]['HTML_PARSED_DESCRIPTION']['HTML'])) {
		if (!empty($item[0]['HTML_DESCRIPTION'])) {

//			$dom = new HtmlDomParser($item[0]['HTML_PARSED_DESCRIPTION']['HTML']);
			$dom = new HtmlDomParser($item[0]['HTML_DESCRIPTION']);

			// Заменяем пути к изображениям на сохраненные

			if (!empty($item[0]['HTML_PARSED_DESCRIPTION']['SAVED_IMAGES'])) {

				foreach ($dom->find('img') as $imageKey => $image) {
					$image->src = $item[0]['HTML_PARSED_DESCRIPTION']['SAVED_IMAGES'][$imageKey];
				}

				$item[0]['HTML_PARSED_DESCRIPTION']['HTML'] = $dom->html();
			}

			// FIXME временно сохраняем список ссылок
			foreach ($dom->find('a') as $linkKey => $link) {
				if (!in_array($link->outertext, $linksArray)) {
					$linksArray[$item[0]['NAME']][] = $link->outertext;
				}
			}

			// TODO получать ссылку на этот файл?
			// Заменяем ссылку на таблицу размеров BodyGlove
			foreach ($dom->find('a') as $linkKey => $link){
				if (stripos($link, '/info/body-glove/') !== false){
//					echo $link . PHP_EOL;
					$link->href = '/include/size_table.php';
//					echo "Изменение адреса ссылки на таблицу размеров: " . $link . PHP_EOL;
				}
				$item[0]['HTML_PARSED_DESCRIPTION']['HTML'] = $dom->html();
			}


			$dom->clear();
			unset($dom);
		}

		$itemFieldsArray = [
			"MODIFIED_BY" => $USER->GetID(),
			"IBLOCK_ID" => $IBlockCatalogId,
			"IBLOCK_SECTION_ID" => TEMP_CATALOG_SECTION,
			"NAME" => $itemName,
			"CODE" => CUtil::translit($itemName . ' ' . $item[0]["OFFER_ID"], "ru", $translitParams),
			"ACTIVE" => "Y",
//			"DETAIL_PICTURE" => (isset($item[0]["PICTURES"][0])) ? CFile::MakeFileArray($item[0]["PICTURES"][0]) : "",
			"DETAIL_PICTURE" => (isset($item[0]["HTML_DETAIL_PICTURE_URL"])) ? CFile::MakeFileArray($item[0]["HTML_DETAIL_PICTURE_URL"]) : "",
			"DETAIL_TEXT" => (!empty ($item[0]["HTML_PARSED_DESCRIPTION"]["HTML"])) ? html_entity_decode($item[0]["HTML_PARSED_DESCRIPTION"]["HTML"]) : "",
			"PROPERTY_VALUES" => [
				"SITE_NAME" => P_SITE_NAME,
				"P_GROUP_ID" => $key, // Идентификатор, по которому осуществляется связь товаров в XML и торговом каталоге
				"CATEGORY_ID" => $item[0]["CATEGORY_ID"],
				"MORE_PHOTO" => (!empty($item[0]["MORE_PHOTO"])) ? $item[0]["MORE_PHOTO"] : "",
				"SKIBOARD_ITEM_TYPE" => $itemTypeId > 0 ? $itemTypeId : '',
				"SKIBOARD_ITEM_PURPOSE" => $itemPurposeId > 0 ? $itemPurposeId : ''
			]
		];

		if ($productId = $obElement->Add($itemFieldsArray)) {
			echo "Добавлен товар " . $productId . "\n";
		} else {
			echo "Ошибка добавления товара: " . str_replace("<br>", "", $obElement->LAST_ERROR) . "\n";
			continue;
		}

		if ($productId) {

			// FIXME uppercase

			if (!empty($manValueIdPairsArray[strtoupper($item[0]["BRAND"])])) {
				$manXmlId = $manValueIdPairsArray[strtoupper($item[0]["BRAND"])];
			} else if (!empty($manValueIdPairsArray[$item[0]["BRAND"]])) {
				$manXmlId = $manValueIdPairsArray[$item[0]["BRAND"]];
			} else if (!empty($manValueIdPairsArray[strtolower($item[0]["BRAND"])])) {
				$manXmlId = $manValueIdPairsArray[strtolower($item[0]["BRAND"])];
			} else if (!empty($manValueIdPairsArray[ucfirst(strtolower($item[0]["BRAND"]))])) {
				$manXmlId = $manValueIdPairsArray[ucfirst(strtolower($item[0]["BRAND"]))];
			}

			/*
			$manXmlId = (!empty($manValueIdPairsArray[strtoupper($item[0]["BRAND"])]))
				? ($manValueIdPairsArray[strtoupper($item[0]["BRAND"])])
				: ($manValueIdPairsArray[$item[0]["BRAND"]]);
			*/

			// Запись значения свойства "Производитель". Передается UF_XML_ID из хайлоад-блока
			if (!empty ($manXmlId)) {
				CIBlockElement::SetPropertyValuesEx($productId, $IBlockCatalogId, array("MANUFACTURER" => $manXmlId));
			}

			foreach ($item as $k => $offer) {

				$obElement = new CIBlockElement();

				$offerPrice = $offer["PRICE"];

				$arOfferProps = [
					$SKUPropertyId => $productId,
					'SIZE' => $valueIdPairsArray[$offer['ATTRIBUTES']['Размер']] ?: SIZE_PROPERTY_VALUE__ONE_SIZE,
//					'SKIBOARD_EXTERNAL_OFFER_ID' => $offer['OFFER_ID']
					'P_KITERU_EXTERNAL_OFFER_ID' => $offer['OFFER_ID']
				];

				foreach ($offer['ATTRIBUTES'] as $propertyName => $propertyValue) {
					$arOfferProps[strtoupper(CUtil::translit($propertyName, 'ru', $translitParams))] = $propertyValue;
				}

				// Собираем название торгового предложения
				if (!empty($offer["ATTRIBUTES"]["variation_sku"])) {
					$offerName = $offer["ATTRIBUTES"]["variation_sku"];
				} else {
					$offerName = (!empty($offer["SHORT_NAME"])) ? $offer["SHORT_NAME"] . " " . $offer["ATTRIBUTES"]["Размер"] : $offer["NAME"];
				}

				$offerName = preg_replace( "/\r|\n/", "", $offerName); // удалим переносы строк

				$arOfferFields = [
					'NAME' => $offerName,
					'IBLOCK_ID' => SKU_IBLOCK_ID,
					'ACTIVE' => 'Y',
					"DETAIL_PICTURE" => (isset($offer["HTML_DETAIL_PICTURE_URL"])) ? CFile::MakeFileArray($offer["HTML_DETAIL_PICTURE_URL"]) : "",
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

					echo "Добавлено торговое предложение " . $offerId . PHP_EOL;

				}
			}
		}
	} catch (Exception $e) {
		echo $e->getMessage() . PHP_EOL;
	}
}

//file_put_contents(__DIR__ . "/logs/LinksArray.log", print_r($linksArray, true));
//file_put_contents(__DIR__ . "/logs/resultArrayAfterAdd.log", print_r($resultArray, true));

//--------------------------------------КОНЕЦ СОХРАНЕНИЯ (ADD) ЭЛЕМЕНТОВ----------------------------------------------//