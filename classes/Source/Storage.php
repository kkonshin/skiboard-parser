<?php

namespace Parser\Source;

class Storage extends Source
{
	/**
	 * Метод сохраняет текущий файл XML
	 * @param Source $source
	 * @param $sourceSavePath
	 * @return string
	 */

	private static $sourceSavePath = SOURCE_SAVE_PATH . "previous.xml";

	public static function storeCurrentXml(Source $source)
	{
		try {

			$source = $source->getSource();

			$sourceSavePath = self::$sourceSavePath;

			if (is_file($sourceSavePath)){
				echo "Файл {$sourceSavePath} будет перезаписан." . PHP_EOL;
				$result = file_put_contents($sourceSavePath, $source);
			} else {
				echo "Файл {$sourceSavePath} не найден." . PHP_EOL;
				$result = file_put_contents($sourceSavePath, $source);
			}

			if ($result) {
				return "Файл {$sourceSavePath} успешно сохранен." . PHP_EOL;
			} else {
				throw new \Exception("Ошибка сохранения файла каталога." . PHP_EOL);
			}
		} catch (\Exception $exception) {
			return $exception->getMessage();
		}
	}

	public static function getPreviousXml()
	{
		if (is_file(self::$sourceSavePath)){
			return file_get_contents(self::$sourceSavePath);
		} else {
			return false;
		}

	}

	public static function rename()
	{
		
	}

}