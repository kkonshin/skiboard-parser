# skiboard-parser
skiboard.ru parser

Для ручного запуска парсера скопировать репозиторий, перейти в папку 
skiboard-parser и запустить скрипт командой консоли php -f console.php 

Настройки парсера производятся в файле конфигурации config.php

1. Время выполнения скрипта
2. Отображение ошибок
3. Установка констант:

SOURCE = адрес .xml файла
SAVE_FILE = адрес временного файла, куда сохраняются результаты парсинга до сохранения в инфоблоке
[функция в разработке].
SKU_IBLOCK_ID = ID инфоблока торговых предложений
HIGHLOAD_ID = ID хайлоад блока со справочником производителей
