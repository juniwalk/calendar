<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

final class SourceTypeHandledException extends CalendarException
{
	public static function fromHandler(string $handler): static
	{
		return new static('Source handler "'.$handler.'" has already been added.');
	}
}
