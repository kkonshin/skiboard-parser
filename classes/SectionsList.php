<?php

namespace Parser;

interface SectionsListInterface
{
	// TODO нужен не только список новых индексов разделов, но их названия
	public static function getOldSections();

	public static function getNewSections();

	public static function compareSections();
}
class SectionsList implements SectionsListInterface
{
	
}