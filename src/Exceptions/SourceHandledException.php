<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

use JuniWalk\Calendar\Source;

final class SourceHandledException extends CalendarException
{
	public static function fromHandler(string $handler, Source $source): static
	{
		return new static('Source handler "'.$handler.'" has already been added by "'.$source->getName().'".');
	}
}
