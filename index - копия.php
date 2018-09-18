<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?
//ini_set('display_errors', 1);
//ini_set('error_reporting', E_ALL);

require_once("vendor/autoload.php");

use Symfony\Component\DomCrawler\Crawler;

define('SAVE_FILE', __DIR__ . "/save/parser_dump.php");


// TODO исключать товары с пустой ценой?
// TODO разнести офферы по категориям в зависимости от ID
// TODO - картинки сразу скачивать, а не ссылаться на сторонний сайт. resize_image_get
// Удалить лишнее в конце

// TODO - проверять сохранение элементов для первых 10 элементов
// TODO ----Торговые предложения----Свойство для отбора----Родительский товар
// TODO буферизация, сохранение на диск, скачивание по частям?

//-------------------------------------------------ПАРСЕР-------------------------------------------------------------//


function parse (){

	$offers = [];
	$names = [];
	$prices = [];
	$params = [];
	$categoriesIds = [];
	$picturesUrls = [];

	$categoriesArray = [];

	$ta = [];
	$resultArray = [];

	$xml = file_get_contents("http://b2b.skiboard.ru/yml_get/uzvev7kr159d");

	$crawler = new Crawler($xml);
	$offers = $crawler->filter('offer');

	try {
			// Все параметры всех офферов
			$allItems = $offers->each(function (Crawler $node, $i) {
				return $node->children();
			});
			// Получаем массив свойств для каждого оффера
			foreach ($allItems as $key => $item) {
				foreach ($item as $k => $v) {
//		$ta[$key][] = $v;
					if ($v->nodeName === 'name') {
						$ta[$key]['NAME'] = $v->nodeValue;
					}
					if ($v->nodeName === 'price') {
						$ta[$key]['PRICE'] = $v->nodeValue;
					}

					// Исключаем категории
					if ($v->nodeName === 'categoryId' && !in_array($v->nodeValue,
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
					if ($v->nodeName === 'picture') {
						$ta[$key]['PICTURES'][] = $v->nodeValue;
					}
					if ($v->nodeName === 'description') {
						$ta[$key]['DESCRIPTION'] = $v->nodeValue;
					}
					$ta[$key]['ATTRIBUTES'] = $item->filter('param')->extract(['name', '_text']);
				}
			}


			echo "<pre>";
			print_r($ta);
			echo "</pre>";

			// Сохраняем результаты парсинга, чтобы не парсить по несколько раз (DEVELOPMENT)
			/*
			if (count($ta) > 0) {
				file_put_contents(__DIR__ . "/save/parser_dump.php", var_export($ta, true));
			}
			*/
	} catch (Exception $e) {
		echo $e->getMessage();
	}
}

//parse();
//-------------------------------------------КОНЕЦ ПАРСЕРА------------------------------------------------------------//

//-----------------------------------------СОХРАНЕНИЕ ЭЛЕМЕНТОВ-------------------------------------------------------//
$firstTenElements = array_slice($ta, 0, 10, true);
echo "<pre>";
print_r($ta);
echo "</pre>";

//--------------------------------------КОНЕЦ СОХРАНЕНИЯ ЭЛЕМЕНТОВ----------------------------------------------------//



?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>