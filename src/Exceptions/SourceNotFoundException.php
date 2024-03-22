<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

final class SourceNotFoundException extends CalendarException
{
	public static function fromType(string $type): static
	{
		return new static('Source for type '.$type.' could not be found, did you register it?');
	}
}
