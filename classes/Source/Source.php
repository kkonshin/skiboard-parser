<?php

namespace Parser\Source;

/**
 * Class Source
 * @package Parser
 *
 * Базовый класс, предназначенный для работы с файлом-источником
 */
class Source
{
	private $sourcePath;

	public function __construct($sourcePath)
	{

		// TODO parent::__construct

		$this->sourcePath = $sourcePath;
	}

	/**
	 * @return mixed
	 */
	public function getSourcePath()
	{
		return $this->sourcePath;
	}

	/**
	 * @param mixed $sourcePath
	 */
	public function setSourcePath($sourcePath)
	{
		$this->sourcePath = $sourcePath;
	}

	public function getSource()
	{
		try {

			$source = file_get_contents($this->getSourcePath());

			return $source;

		} catch (\Exception $exception) {

			return $exception->getMessage();

		}
	}
}