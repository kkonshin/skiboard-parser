<?php

namespace Parser;

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

		// TODO использовать результат проверки класса CatalogDate

	}
}