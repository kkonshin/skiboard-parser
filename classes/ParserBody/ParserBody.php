<?php

namespace Parser\ParserBody;

use Symfony\Component\DomCrawler\Crawler;

class ParserBody
{

	/**
	 * Метод парсит экземпляр краулера Symfony.
	 * Возвращается массив вида: ID товара => Торговые предложения
	 * @param Crawler|null $crawler
	 * @return array|string
	 */

	private static $colorsCount = 0;
	private static $oneOfferGoodsCount = 0;
	private static $colorsArray = [];
	private static $itemsToSplit = [];
	private static $groupedItemsArray = [];

	public static function parse(Crawler $crawler = null)
	{
		$ta = [];

		$sourceDate = $crawler->filter('yml_catalog')->attr('date');

		echo "Разбираем каталог от " . $sourceDate . PHP_EOL;

		$offers = $crawler->filter('offer');

		$parentItemsIdsArray = [];

//		$groupedItemsArray = [];

		try {
			// Все параметры всех офферов
			$allItems = $offers->each(function (Crawler $node, $i) {
				return $node->children();
			});

			$groupIds = $offers->each(function (Crawler $node, $i) {
				return $node->attr('group_id');
			});

			$offerIds = $offers->each(function (Crawler $node, $i) {
				return $node->attr('id');
			});

			// Получаем массив свойств для каждого оффера

			foreach ($allItems as $key => $item) {
				foreach ($item as $k => $v) {

					$ta[$key]["PARENT_ITEM_ID"] = (!empty($groupIds[$key])) ? $groupIds[$key] : $offerIds[$key];

					$ta[$key]["OFFER_ID"] = $offerIds[$key];

					if ($v->nodeName === 'name') {
						$ta[$key]['NAME'] = $v->nodeValue;
					}

					if ($v->nodeName === 'price') {
						$ta[$key]['PRICE'] = $v->nodeValue;
					}

					// Исключаем категории

					if ($v->nodeName === 'categoryId' && !in_array(trim((string)$v->nodeValue),
							[
								// Здесь список исключаемых категорий
							]
						)
					) {
						$ta[$key]['CATEGORY_ID'] = $v->nodeValue;

					}

					if ($v->nodeName === 'vendor') {
						$ta[$key]['BRAND'] = $v->nodeValue;
					}

					if ($v->nodeName === 'picture') {
						$ta[$key]['PICTURES'][] = $v->nodeValue;
					}
					if ($v->nodeName === 'description') {
						$ta[$key]['DESCRIPTION'] = $v->nodeValue;
					}
					$ta[$key]['ATTRIBUTES'] = $item->filter('param')->extract(['name', '_text']);
				}
			}

			// Развернем полученный через extract массив атрибутов

			foreach ($ta as $key => $value) {
				foreach ($value as $k => $v) {
					if ($k === "ATTRIBUTES") {
						foreach ($v as $i => $attribute) {
							$ta[$key][$k][$i] = array_flip($ta[$key][$k][$i]);
							$ta[$key][$k][$attribute[0]] = $attribute[1];
							unset($ta[$key][$k][$i]);
						}
					}
				}
			}

			// Получим массив уникальных ID родительских товаров
			foreach ($ta as $key => $value) {
				$parentItemsIdsArray[] = $value["PARENT_ITEM_ID"];
			}

			$parentItemsIdsArray = array_unique($parentItemsIdsArray);

			// Разобъем исходный массив по родительским товарам, исключая товары с ценой 0 и товары без категории
			foreach ($parentItemsIdsArray as $key => $id) {
				foreach ($ta as $k => $item) {
					if ($id === $item["PARENT_ITEM_ID"] && (int)$item["PRICE"] > 0 && !empty($item["CATEGORY_ID"])) {
						self::$groupedItemsArray[$id][] = $item;
					}
				}
			}

			// Возможны товары у которых 1 ТП и есть свойство цвет

			foreach (self::$groupedItemsArray as $key => $value) {

				if (count($value) > 1) {

					foreach ($value as $k => $offer) {

						if (isset($offer['ATTRIBUTES']['Цвет']) && strlen($offer['ATTRIBUTES']['Цвет']) > 0) {
							// Количество товаров у которых есть атрибут 'Цвет' и количество ТП больше одного
							self::$colorsCount += 1;
//							if (!in_array($offer['ATTRIBUTES']['Цвет'], self::$colorsArray[$value[0]['NAME']]['COLORS'])) {
							if (!in_array($offer['ATTRIBUTES']['Цвет'], self::$colorsArray[$value[0]['NAME']])) {
//								self::$colorsArray[$value[0]['NAME']]['COLORS'][] = $offer['ATTRIBUTES']['Цвет'];
								self::$colorsArray[$value[0]['NAME']][] = $offer['ATTRIBUTES']['Цвет'];
							}
						}
					}

					// Для каждого товара суммируем количество цветов

				} elseif (count($value) === 1 && !empty($value[0]['ATTRIBUTES']['Цвет'])) {
//					echo $value[0]['NAME'] . ' ' . $value[0]['ATTRIBUTES']['Цвет'] .  PHP_EOL;
					self::$oneOfferGoodsCount += 1;
				}
			}

			// удаляем элементы из массива товаров, у которых есть цвет, если цвет только один
			foreach (self::$colorsArray as $name => $colors) {
//				if (count($colors['COLORS']) === 1) {
				if (count($colors) === 1) {
					unset (self::$colorsArray[$name]);
				}
			}

//			file_put_contents(__DIR__ . "/colorsArray.log", print_r(self::$colorsArray, true));
//			file_put_contents(__DIR__ . "/colorsArray.log", print_r(self::$colorsCount, true));

			// Возможен товар у которого несколько ТП по размерам, но все они одного цвета

			// выберем из self::$groupedItemsArray товары из массива self::$colorsArray
			// TODO temporary, удалить в процессе рефакторинга

			foreach (self::$groupedItemsArray as $key => $groupedItem) {
				foreach (self::$colorsArray as $name => $colors) {
					if ($groupedItem[0]['NAME'] === $name) {
						// Массив для отладки
						self::$itemsToSplit[] = $groupedItem;
						// Количество групп разбиения
						self::$groupedItemsArray[$key]['PARTS_COUNT'] = count($colors);
					}
				}
			}

			// Временно разобъем массив self::$itemsToSplit


			// TODO !!! Удалить после переноса в groupedItemsArray
			/*
			foreach (self::$itemsToSplit as $itemKey => $itemValue){
				foreach ($itemValue as $offerKey => $offerValue){
//					foreach (self::$colorsArray[$offerValue['NAME']]['COLORS'] as $colorKey => $colorValue){
					foreach (self::$colorsArray[$offerValue['NAME']] as $colorKey => $colorValue){
						if($colorValue === $offerValue['ATTRIBUTES']['Цвет']){
//							echo $offerValue['NAME'] . ' ' . $offerValue['ATTRIBUTES']['Цвет'] . ' размер ' . $offerValue['ATTRIBUTES']['Размер'] . PHP_EOL;
//							self::$itemsToSplit[$itemKey]['PARTS'][$colorValue] = $offerValue;
							self::$itemsToSplit[$itemKey]['PARTS'][$colorValue][] = $offerValue;
						}
					}
				}
			}
			*/
			foreach (self::$groupedItemsArray as $itemKey => $itemValue) {
				foreach ($itemValue as $offerKey => $offerValue) {
//					foreach (self::$colorsArray[$offerValue['NAME']]['COLORS'] as $colorKey => $colorValue){
					foreach (self::$colorsArray[$offerValue['NAME']] as $colorKey => $colorValue) {
						if ($colorValue === $offerValue['ATTRIBUTES']['Цвет']) {
//							echo $offerValue['NAME'] . ' ' . $offerValue['ATTRIBUTES']['Цвет'] . ' размер ' . $offerValue['ATTRIBUTES']['Размер'] . PHP_EOL;
//							self::$itemsToSplit[$itemKey]['PARTS'][$colorValue] = $offerValue;
							self::$groupedItemsArray[$itemKey]['PARTS'][$colorValue][] = $offerValue;
						}
					}
				}
			}

//			file_put_contents(__DIR__ . "/colorsArray.log", print_r(self::$colorsArray, true));

			// Для каждого из 269 товаров нужно создать разбиение внутри товара, количество подмассивов
			// будет равно количеству цветов для этого товара

//			file_put_contents(__DIR__ . "/itemsToSplit.log", print_r(self::$itemsToSplit, true));

//			echo "Всего товаров с одним торговым предложением и атрибутом 'Цвет': " . self::$oneOfferGoodsCount . PHP_EOL;
//			echo "Всего товаров с атрибутом 'Цвет' с несколькими торговыми предложениями: " . self::$colorsCount . PHP_EOL;

			return self::$groupedItemsArray;

		} catch (\Exception $e) {
			return $e->getMessage();
		}
}
}