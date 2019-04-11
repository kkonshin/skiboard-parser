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

	/**
	 * Возвращает путь к последнему сохраненому файлу каталога
	 * @return string
	 */

	public static function getSourceSavePath()
	{
		return self::$sourceSavePath;
	}

	public static function storeCurrentXml(Source $source)
	{
		try {

			$source = $source->getSource();

			$sourceSavePath = self::$sourceSavePath;

			if (is_file($sourceSavePath)) {
				echo "Файл {$sourceSavePath} будет перезаписан." . PHP_EOL;
				$result = file_put_contents($sourceSavePath, $source);
			} else {
				echo "Файл {$sourceSavePath} не найден. Будет произведено первичное заполнение временного раздела каталога" . PHP_EOL;
				$result = file_put_contents($sourceSavePath, $source);
			}

			if ($result) {
//				return "Файл {$sourceSavePath} успешно сохранен." . PHP_EOL;
				return $sourceSavePath;
			} else {
				throw new \Exception("Ошибка сохранения файла каталога." . PHP_EOL);
			}
		} catch (\Exception $exception) {
			return $exception->getMessage();
		}
	}

	/**
	 * Возвращает содержимое последнего сохраненного файла каталога
	 * @return bool|string
	 */

	public static function getPreviousXml()
	{
		if (is_file(self::$sourceSavePath)) {
			return file_get_contents(self::$sourceSavePath);
		} else {
			return false;
		}
	}

	/**
	 * Переименовывает старый файл каталога перед сохранением нового
	 * @param $pathToFile
	 * @return bool
	 */

	public static function rename($pathToFile)
	{
		$result = false;
		if (is_file($pathToFile)) {
			$result = rename($pathToFile,
				__DIR__
				. "/save/"
				. explode('.', basename($pathToFile))[0]
				. "__"
				. date("Y_m_d__H_i_s")
				. ".xml"
			);
		}
		if ($result) {
			return $result;
		} else {
			return false;
		}
	}
}