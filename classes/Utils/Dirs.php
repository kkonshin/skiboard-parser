<?php

namespace Parser\Utils;

class Dirs
{
	/**
	 * Создает необходимые рабочие директории в корне папки парсера
	 * @param $basedir
	 */
	public static function make($basedir)
	{
		if (!is_dir($basedir . "/logs")) {
			mkdir($basedir. "/logs", 0775, true);
		}
		if (!is_dir($basedir . "/save")) {
			mkdir($basedir. "/save", 0775, true);
		}
	}
}
