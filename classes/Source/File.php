<?php
namespace Parser\File;

class File
{
	/**
	 * Форматирует размер файла в человекопонятный вид
	 * @param $bytes
	 * @return string
	 */

	private static function formatSizeUnits($bytes)
	{
		if ($bytes >= 1073741824) {
			$bytes = number_format($bytes / 1073741824, 2) . ' GB';
		} elseif ($bytes >= 1048576) {
			$bytes = number_format($bytes / 1048576, 2) . ' MB';
		} elseif ($bytes >= 1024) {
			$bytes = number_format($bytes / 1024, 2) . ' KB';
		} elseif ($bytes > 1) {
			$bytes = $bytes . ' bytes';
		} elseif ($bytes == 1) {
			$bytes = $bytes . ' byte';
		} else {
			$bytes = '0 bytes';
		}
		return $bytes;
	}

	/**
	 * Возвращает размер файла в человекопонятном виде
	 * @param $file
	 * @return bool|string
	 */

	public static function getFileSize($file)
	{

		if (is_file($file)) {

			$size = filesize($file);

			$formattedSize = self::formatSizeUnits($size);

			return $formattedSize;

		} else {
			return false;
		}
	}



}