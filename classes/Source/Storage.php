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

				echo "Файл {$sourceSavePath} уже существует." . PHP_EOL;

				$result = false;

			} else {

				$result = file_put_contents($sourceSavePath, $source);

			}

			if ($result) {

				return "current.xml успешно сохранен.";

			} else {

				throw new \Exception("Ошибка сохранения файла каталога");

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