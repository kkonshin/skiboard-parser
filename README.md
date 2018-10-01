# skiboard-parser
### skiboard.ru parser

Для разбора DOM парсер использует компонент symfony/dom-crawler

Для ручного запуска парсера скопировать репозиторий, перейти в папку 
skiboard-parser и запустить скрипт командой консоли php -f console.php 

Для настройки автоматического запуска необходимо в файл
/www/bitrix/crontab/crontab.cfg добавить (или раскомментировать) строку:

0 1 * * * /usr/bin/php -f /home/bitrix/www/test/skiboard-parser/ > /home/bitrix/www/test/skiboard-parser/logs/crontab.log 2>&1

, где 0 1 - запуск в 01:00 ежедневно,
 
/usr/bin/php - путь к интерпретатору php

/home/bitrix/www/test/skiboard-parser/console.php - путь к исполняемому файлу парсера

/home/bitrix/www/test/skiboard-parser/logs/crontab.log - путь к логам крона

После чего выполнить в командной строке на сервере команду:

crontab ~/www/bitrix/crontab/crontab.cfg

Проверить, что настройки добавлены:

crontab -l 

> При первом запуске происходит полный разбор .xml файла и 
запись всех товаров и ТП во временный раздел инфоблока каталога.
Требуется не менее 10гб свободного места на диске для сохранения
временных файлов изображений.

>Для того, чтобы первый запуск парсера записал все товары из каталога во 
временный раздел, в папке /save/ должен ОТСУТСТВОВАТЬ файл previous.xml

Настройки парсера производятся в файле конфигурации config.php

- Время выполнения скрипта
- Отображение ошибок
- Установка констант и пр.

SOURCE = внешний адрес .xml файла

SOURCE_SAVE_PATH = адрес директории для сохранения .xml файла
 
SKU_IBLOCK_ID = ID инфоблока торговых предложений

HIGHLOAD_ID = ID хайлоад блока со справочником производителей

Массивы $summer и $winter содержат списки категорий товаров летнего и зимнего 
ассортимента. Это влияет на установку цен.

>Если появится новая категория товаров, ее необходимо будет добавить в один из
этих массивов.

После первого заполнения временного раздела инфоблока, парсер сохраняет в
папке /save/ скачанный .xml файл каталога.
 
При следующем запуске сравнивается дата выгрузки в сохраненном файле и в
файле на удаленном сервере. Если даты совпадают, обновления каталога не происходит.

Если количество товаров в каталоге skiboard.ru изменилось - парсер либо деактивирует отсутствующие
в новом каталоге товары, либо запишет в инфоблок новые товары.

Также автоматически произойдет добавление новых свойств торговых предложений и 
значений свойств типа "список" и "справочник".

Файл add.php отвечает за запись товаров и торговых предложений во временный раздел каталога,
привязку к разделам, запись свойств товара и установку цен торговых предложений. 
Используется при первичном заполнении раздела и при добавлении новых товаров.


