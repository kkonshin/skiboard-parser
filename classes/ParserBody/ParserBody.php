<?php

namespace Parser\ParserBody;

use Symfony\Component\DomCrawler\Crawler;

class ParserBody
{
	private static $colorsArray = [];
	private static $groupedItemsArray = [];
	private static $ta = [];
	private static $parentItemsIdsArray = [];

	/**
	 * Метод парсит экземпляр краулера Symfony.
	 * Возвращается массив вида: ID товара => Торговые предложения
	 * @param Crawler|null $crawler
	 * @return array|string
	 */

	public static function parse(Crawler $crawler = null)
	{
		$sourceDate = $crawler->filter('yml_catalog')->attr('date');

		echo "Разбираем каталог от " . $sourceDate . PHP_EOL;

		$offers = $crawler->filter('offer');

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

					self::$ta[$key]["PARENT_ITEM_ID"] = (!empty($groupIds[$key])) ? $groupIds[$key] : $offerIds[$key];

					self::$ta[$key]["OFFER_ID"] = $offerIds[$key];

					if ($v->nodeName === 'name') {
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

			self::$parentItemsIdsArray = array_unique(self::$parentItemsIdsArray);

			foreach (self::$parentItemsIdsArray as $key => $id) {
				foreach (self::$ta as $k => $item) {
					if ($id === $item["ATTRIBUTES"]['Артикул'] && (int)$item["PRICE"] > 0 && !empty($item["CATEGORY_ID"])) {
						self::$groupedItemsArray[$id][] = $item;
					}
				}
			}


			// Вырезаем из названий ТП цвет и размер, записываем в соответствующие атрибуты

            foreach (self::$groupedItemsArray as $key => $value){
			    if (count($value) > 1) {
			        foreach ($value as $skuKey => $sku){
                        $match = preg_match_all('/(\(([^()]|(?R))*\))/', $sku["NAME"], $matches);
                        if ($match){

                            // FIXME - это для случая, когда есть цвет и размер, предусмотреть только один параметр
                            // FIXME санитизировать эти атрибуты (в тч убрать переносы строк)

                            self::$groupedItemsArray[$key][$skuKey]['ATTRIBUTES']['Размер'] = trim(substr(explode(",", $matches[0][count($matches[0])-1])[0], 1));
                            self::$groupedItemsArray[$key][$skuKey]['ATTRIBUTES']['Цвет'] = trim(substr(explode(",", $matches[0][count($matches[0])-1])[count(count($matches[0])-1)], 0, -1));
                        }
                    }

                }
            }


//			file_put_contents(__DIR__ . "/goupedItemsArray__fromArticle.log", print_r(self::$groupedItemsArray, true));

			// TODO цвета и размера в атрибутах нет - парсить из названия
			// TODO проверить ['PARENT_ITEM_ID']

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

			// TODO проверить ['PARENT_ITEM_ID']

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
						foreach ($offerValue as $colorKey => $colorValue){
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

//			file_put_contents(__DIR__ . "/colorsArray.log", print_r(self::$colorsArray, true));

			return self::$groupedItemsArray;

		} catch (\Exception $e) {
			return $e->getMessage();
		}
}
}