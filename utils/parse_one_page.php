#!/usr/bin/php

<?php

// Позволяет сохранить одну произвольную страницу вместе с зависимостями через стрим,
// выбрать только разметку, удалив скрипты
// (в данном случае https://www.kite.ru/info/body-glove/size-chart-body-glove.php )

if (php_sapi_name() !== "cli") {
	die ('Этот скрипт предназначен для запуска из командной строки');
}

require(__DIR__ . "/../config.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once("../vendor/autoload.php");

use Parser\HtmlParser\HtmlParser;
use voku\helper\HtmlDomParser;

while (ob_get_level()) {
	ob_end_flush();
}

$url = "//www.kite.ru/info/body-glove/size-chart-body-glove.php";
$linksArray = []; // Ссылки на css, шрифты, иконки

try {

    $body = HtmlParser::getBody($url);

	if ($body) {

//		file_put_contents(SOURCE_SAVE_PATH . "size-chart-body-glove.php", print_r($body, true));

		$dom = new HtmlDomParser($body);

		$mainDiv = $dom->find('.main-content')[0];

		$sizeTable = new HtmlDomParser($mainDiv);

		// Удаляем лишние элементы
		foreach ($sizeTable->find('aside') as $aside) {
			$aside->outertext = '';
		}

		foreach ($sizeTable->find('img') as $img) {
			$img->outertext = '';
		}

		foreach ($sizeTable->find('br') as $br) {
			$br->outertext = '';
		}

		// добавим хедер и футер
        $wrapped = HtmlParser::wrap($sizeTable->html());

		// Список ссылок на css
//		foreach ($dom->find('link[rel="stylesheet"]') as $link) {
//			$linksArray[] = $link;
//		}

		// Сохраняет ссылки на CSS
//		$i = 0;
//		foreach ( $linksArray as $link ){
//		    if (stripos($link, 'http') === false){
//				$body = HtmlParser::getBody(P_SITE_BASE_NAME . $link->href);
//            }
//			file_put_contents(SOURCE_SAVE_PATH . "_" . $i . ".css", print_r($body, true));
//			$i++;
//        }

		if (!file_exists($_SERVER["DOCUMENT_ROOT"] . "/include/size_table.php")){
			file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/include/size_table.php", print_r($wrapped, true));
        }
	}
} catch (Exception $e) {
	echo $e->getMessage() . PHP_EOL;
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
