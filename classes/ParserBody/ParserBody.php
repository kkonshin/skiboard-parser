<?php

namespace Parser\ParserBody;

use Symfony\Component\DomCrawler\Crawler;

// Требуется рефактор

class ParserBody
{
	private static $colorsArray = [];
	private static $groupedItemsArray = [];
	private static $ta = [];
	private static $parentItemsIdsArray = [];
	private static $categoriesArray = [];
	private static $availableCategoriesArray = [];
	private static $toSetZeroQuantityArray = [];

	/**
	 * Возвращает список категорий, название которых отличается от "Нет в наличии"
	 * @return array
	 */
 	public static function getAvailableCategories()
	{
		return self::$availableCategoriesArray;
	}

	/**
	 * Возвращает массив внешних ключей торговых предложений, количество которых должно быть установлено в ноль
	 * @return array
	 */
	public static function getZeroQuantity()
	{
		return self::$toSetZeroQuantityArray;
	}

	/**
	 * Получаем массив категорий
	 * @param Crawler|null $crawler
	 */
	private static function getCategories(Crawler $crawler = null)
	{
		$categories = $crawler->filter('category')->extract(['id', '_text']);
		foreach ($categories as $key => $value) {
			self::$categoriesArray[$value[0]] = $value[1];
		}
	}

	/**
	 * Возвращает массив категорий "Нет в наличии"
	 * @param array $categories
	 */
	private static function filterCategories(Array $categories)
	{
		foreach ($categories as $key => $value) {
			if (trim(mb_strtolower($value)) !== "нет в наличии") {
				self::$availableCategoriesArray[] = $key;
			}
		}
//		file_put_contents(__DIR__ . "/../../logs/ParserBody__filterCategories.log", print_r(self::$notAvailableCategoriesArray, true));
	}

	/**
	 * Метод парсит экземпляр краулера Symfony.
	 * Возвращается массив вида: ID товара => Торговые предложения
	 * @param Crawler|null $crawler
	 * @return array|string
	 */

	public static function parse(Crawler $crawler = null)
	{
		try {

			$sourceDate = $crawler->filter('yml_catalog')->attr('date');

			echo "Разбираем каталог от " . $sourceDate . PHP_EOL;

			$offers = $crawler->filter('offer');

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

			$available = $offers->each(function (Crawler $node) {
				return $node->attr('available');
			});

			// Получаем массив свойств для каждого оффера

			foreach ($allItems as $key => $item) {
				foreach ($item as $k => $v) {

					self::$ta[$key]["PARENT_ITEM_ID"] = (!empty($groupIds[$key])) ? $groupIds[$key] : $offerIds[$key];

					self::$ta[$key]["OFFER_ID"] = $offerIds[$key];

					self::$ta[$key]["AVAILABLE"] = $available[$key];

					if ($v->nodeName === 'name') {
//                        self::$ta[$key]['NAME'] = preg_replace('/(\r\n|\r|\n)/', '', trim($v->nodeValue));
						self::$ta[$key]['NAME'] = trim($v->nodeValue);
					}

					if ($v->nodeName === 'price') {
						self::$ta[$key]['PRICE'] = $v->nodeValue;
					}

					// Исключаем категории

					if ($v->nodeName === 'categoryId' && !in_array(trim((string)$v->nodeValue),
							[
								// Здесь список исключаемых категорий
							]
						)
					) {
						self::$ta[$key]['CATEGORY_ID'] = $v->nodeValue;

					}

					if ($v->nodeName === 'url') {
						self::$ta[$key]['URL'] = $v->nodeValue;
					}

					if ($v->nodeName === 'vendor') {
						self::$ta[$key]['BRAND'] = $v->nodeValue;
					}

					if ($v->nodeName === 'picture') {
						self::$ta[$key]['PICTURES'][] = $v->nodeValue;
					}
					if ($v->nodeName === 'description') {
						self::$ta[$key]['DESCRIPTION'] = $v->nodeValue;
					}
					self::$ta[$key]['ATTRIBUTES'] = $item->filter('param')->extract(['name', '_text']);


				}
			}

			// Развернем полученный через extract массив атрибутов
			// $ta - это массив всех торговых предложений, не распределенных по родительским товарам
			foreach (self::$ta as $key => $value) {
				foreach ($value as $k => $v) {
					if ($k === "ATTRIBUTES") {
						foreach ($v as $i => $attribute) {
							self::$ta[$key][$k][$i] = array_flip(self::$ta[$key][$k][$i]);
							self::$ta[$key][$k][$attribute[0]] = $attribute[1];
							unset(self::$ta[$key][$k][$i]);
						}
					}
				}
			}

			//  Из временного массива получаем группы товаров с одинаковым артикулом

			foreach (self::$ta as $key => $value) {
				self::$parentItemsIdsArray[] = $value["ATTRIBUTES"]['Артикул'];
			}

//			file_put_contents(__DIR__ . "/../../logs/parserBody__parentItemsIdsArray--beforeUnique.log", print_r(self::$parentItemsIdsArray, true));

			self::$parentItemsIdsArray = array_unique(self::$parentItemsIdsArray);

			foreach (self::$parentItemsIdsArray as $key => $id) {
				foreach (self::$ta as $k => $item) {
					if ($id == $item["ATTRIBUTES"]['Артикул'] && (int)$item["PRICE"] > 0 && !empty($item["CATEGORY_ID"])) {
						self::$groupedItemsArray[$id][] = $item;
					}
				}
			}
//			file_put_contents(__DIR__ . "/../../logs/parserBody__groupedArray--afterUnique.log", print_r(self::$groupedItemsArray, true));
//            file_put_contents(__DIR__ . "/../../logs/groupedItemsArray__source.log", print_r(self::$groupedItemsArray, true));

			foreach (self::$groupedItemsArray as $key => $value) {
				if (count($value) > 1) {
					foreach ($value as $skuKey => $sku) {
						$match = preg_match_all('/(\(([^()]|(?R))*\))/', $sku["NAME"], $matches);
						if ($match) {
							$lastParens = $matches[0][count($matches[0]) - 1];
							self::$groupedItemsArray[$key][$skuKey]['ATTRIBUTES']['Размер'] = trim(
								substr($lastParens, 1, -1)
							);

//                            $pattern = '/\(' . self::$groupedItemsArray[$key][$skuKey]["ATTRIBUTES"]["Размер"] . '\)/';

							// -2 - это скобки, убранные при записи размера
							$replacementStringLength = -2 - strlen(self::$groupedItemsArray[$key][$skuKey]["ATTRIBUTES"]["Размер"]);

//                            echo "Замена" . " " . self::$groupedItemsArray[$key][$skuKey]['NAME'] . " > " . $pattern . PHP_EOL;

//                            self::$groupedItemsArray[$key][$skuKey]['SHORT_NAME'] = preg_replace(
//                                $pattern,
//                                '',
//                                self::$groupedItemsArray[$key][$skuKey]['NAME']
//                            );
							self::$groupedItemsArray[$key][$skuKey]['SHORT_NAME'] = substr(self::$groupedItemsArray[$key][$skuKey]['NAME'], 0, $replacementStringLength);
						}
					}
				}
			}

			foreach (self::$groupedItemsArray as $key => $value) {
				if (count($value) > 1) {
					foreach ($value as $k => $offer) {
						if (isset($offer['ATTRIBUTES']['Цвет']) && strlen($offer['ATTRIBUTES']['Цвет']) > 0) {
							if (!in_array($offer['ATTRIBUTES']['Цвет'], self::$colorsArray[$value[0]['PARENT_ITEM_ID']])) {
								self::$colorsArray[$value[0]['PARENT_ITEM_ID']][] = $offer['ATTRIBUTES']['Цвет'];
							}
						}
					}
				}
			}

			foreach (self::$colorsArray as $name => $colors) {
				if (count($colors) === 1) {
					unset (self::$colorsArray[$name]);
				}
			}


			foreach (self::$groupedItemsArray as $itemKey => $itemValue) {

				foreach ($itemValue as $offerKey => $offerValue) {

					foreach (self::$colorsArray[$offerValue['PARENT_ITEM_ID']] as $colorKey => $colorValue) {

						if (strtolower(trim($colorValue)) === strtolower(trim($offerValue['ATTRIBUTES']['Цвет']))) {
							self::$groupedItemsArray[$itemKey]['PARTS'][$colorValue][] = $offerValue;
							unset(self::$groupedItemsArray[$itemKey][$offerKey]);
						}
					}
				}
			}

			// TODO название товара сейчас включает размер и цвет первого оффера

			foreach (self::$groupedItemsArray as $itemKey => $itemValue) {
				foreach ($itemValue as $offerKey => $offerValue) {
					if ($offerKey === 'PARTS') {
						foreach ($offerValue as $colorKey => $colorValue) {
							// Добавляем в название ТП цвет
							$colorValue[0]['NAME'] = $colorValue[0]['NAME'] . ' ' . $colorValue[0]['ATTRIBUTES']['Цвет'];
							// Создаем новый товар с ключом Родительский ID + $colorKey;
							self::$groupedItemsArray[$itemKey . '_' . $colorKey] = $colorValue;
							// Оригинальный товар удаляем
							unset(self::$groupedItemsArray[$itemKey]);
						}
					}
				}
			}

//			file_put_contents(__DIR__ . "/../../logs/parserBody__parentItemsIdsArray--beforeFilterCategories.log", print_r(self::$groupedItemsArray, true));

			// Получаем список категорий
			self::getCategories($crawler);
			// Фильтруем список категорий
			self::filterCategories(self::$categoriesArray);

			// Установим всем ТП значение свойства AVAILABLE.
			// Сохраним внешние ключи недоступных ТП в отдельный массив TO_SET_ZERO_QUANTITY
			foreach (self::$groupedItemsArray as $key => $value) {
				if ($key !== "EXTRA") {
					foreach ($value as $offerKey => $offerValue) {
						if (in_array($offerValue["CATEGORY_ID"], self::$availableCategoriesArray)) {
							self::$groupedItemsArray[$key][$offerKey]["AVAILABLE"] = "Y";
						} else {
							self::$groupedItemsArray[$key][$offerKey]["AVAILABLE"] = "N";
							if (!in_array($offerValue["OFFER_ID"], self::$toSetZeroQuantityArray)) {
								self::$toSetZeroQuantityArray[] = $offerValue["OFFER_ID"];
							}
						}
					}
				}
			}

//			file_put_contents(__DIR__ . "/../../logs/parserBody__parentItemsIdsArray--afterFilterCategories.log", print_r(self::$groupedItemsArray, true));

			return self::$groupedItemsArray;

		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}
}
