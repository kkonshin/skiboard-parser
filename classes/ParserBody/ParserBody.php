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

	public static function parse(Crawler $crawler = null)
	{
		$ta = [];

		$sourceDate = $crawler->filter('yml_catalog')->attr('date');

		echo "Разбираем каталог от " . $sourceDate . PHP_EOL;

		$offers = $crawler->filter('offer');

		$parentItemsIdsArray = [];

		$groupedItemsArray = [];

		try {
			// Все параметры всех офферов
			$allItems = $offers->each(function (Crawler $node) {
				return $node->children();
			});

			// ID родительского товара
			$groupIds = $offers->each(function (Crawler $node) {
				return $node->attr('group_id');
			});

			// Доступность
            $available = $offers->each(function (Crawler $node){
               return $node->attr('available');
            });

			$offerIds = $offers->each(function (Crawler $node) {
				return $node->attr('id');
			});

			// Получаем массив свойств для каждого оффера

			foreach ($allItems as $key => $item) {
				foreach ($item as $k => $v) {

					$ta[$key]["PARENT_ITEM_ID"] = $groupIds[$key];

					$ta[$key]["OFFER_ID"] = $offerIds[$key];

					$ta[$key]["AVAILABLE"] = $available[$key];

					if ($v->nodeName === 'name') {
						$ta[$key]['NAME'] = $v->nodeValue;
					}
					if ($v->nodeName === 'price') {
						$ta[$key]['PRICE'] = $v->nodeValue;
					}
                    if ($v->nodeName === 'vendorCode'){
					    $ta[$key]['VENDOR_CODE'] = $v->nodeValue;
                    }
					// Исключаем категории
					if ($v->nodeName === 'categoryId' && !in_array(trim((string)$v->nodeValue),
							[
								'374',
								'375',
								'376',
								'377',
								'378',
								'379',
								'380',
								'366',
								'357'
							]
						)
					) {
						$ta[$key]['CATEGORY_ID'] = $v->nodeValue;
					}

					// TODO применять сезонную надбавку к цене со скидкой
                    // TODO вынести в отдельный класс или метод
                    /*
					if (in_array((int)$ta[$key]['CATEGORY_ID'], SUMMER)) {
						$ta[$key]["SEASON_PRICE"] = round($ta[$key]["PRICE"] * 1.5);
					}

					if (in_array((int)$ta[$key]['CATEGORY_ID'], WINTER)) {
						$ta[$key]["SEASON_PRICE"] = round($ta[$key]["PRICE"] * 1.6);
					}

					if (in_array((int)$ta[$key]['CATEGORY_ID'], SUMMER)) {
						$ta[$key]["SEASON_PRICE"] = round($ta[$key]["DISCOUNT_PRICE"] * 1.5);
					}

					if (in_array((int)$ta[$key]['CATEGORY_ID'], WINTER)) {
						$ta[$key]["SEASON_PRICE"] = round($ta[$key]["DISCOUNT_PRICE"] * 1.6);
					}
                    */

					if ($v->nodeName === 'picture') {
						$ta[$key]['PICTURES'][] = $v->nodeValue;
					}
					if ($v->nodeName === 'description') {
						$ta[$key]['DESCRIPTION'] = $v->nodeValue;
					}
					$ta[$key]['ATTRIBUTES'] = $item->filter('param')->extract(['name', '_text']);
				}
			}

			// TODO здесь вернуть оригинальный ta, остальные операции вынести в методы

			// Развернем полученный через extract массив атрибутов, извлечем размер
			foreach ($ta as $key => $value) {
				foreach ($value as $k => $v) {
					if ($k === "ATTRIBUTES") {
						foreach ($v as $i => $attribute) {
							$ta[$key][$k][$i] = array_flip($ta[$key][$k][$i]);
							if ($attribute[0] === "Размер") {
								$patterns = ['/"{1}/', '/<{1}/', '/>{1}/'];
								$replacement = ['\'\'', ' менее ', ' более '];
								$attribute[1] = preg_replace(
									$patterns,
									$replacement,
									trim(
										explode(
											":",
											preg_split(
												"/;\s+/",
												$attribute[1])[1])[1])
								);
							}
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
						$groupedItemsArray[$id][] = $item;
					}
				}
			}

			return $groupedItemsArray;

		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}
}