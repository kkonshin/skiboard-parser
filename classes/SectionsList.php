<?php

namespace Parser;

/**
 * Только публичные методы, пока существует только для примера
 * Interface SectionsListInterface
 * @package Parser
 */
interface SectionsListInterface
{
	// TODO нужен не только список новых индексов разделов, но их названия

	public function getSections(); // Получаем список категорий из прайса, возвращаем объект списка категорий?

	public static function compareSections(); // Сравниваем списки из старого и нового прайса, принимаем объекты списка категорий?
}

class SectionsList implements SectionsListInterface
{
	public function getSections()
	{
		// Если существует сохраненный файл прайса с датой, отличной от даты текущего прайса - извлечь список
		// старых категорий

		// TODO Вынести класс в отдельный файл

		// TODO использовать результат проверки класса CatalogDate

	}

	public static function compareSections()
	{
		// TODO: Implement compareSections() method.
	}

}