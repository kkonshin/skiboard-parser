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
	public static function storeCurrentXml(Source $source, $sourceSavePath)
	{
		try {

			$source = $source->getSource();
			$sourceSavePath = $sourceSavePath . "current.xml";

			if (is_file($sourceSavePath)){
				throw new \Exception( "Файл {$sourceSavePath} уже существует." . PHP_EOL);
			} else {
				$result = file_put_contents($sourceSavePath, $source);
			}

			if ($result) {
				return "current.xml успешно сохранен." . PHP_EOL;
			} else {
				throw new \Exception("Ошибка сохранения файла каталога." . PHP_EOL);
			}
		} catch (\Exception $exception) {
			return $exception->getMessage();
		}
	}

	public static function getPreviousXml($sourceSavePath)
	{

		$sourceSavePath = $sourceSavePath . "previous.xml";

		if (is_file($sourceSavePath)){
			return file_get_contents($sourceSavePath);
		} else {
			return false;
		}

	}
}