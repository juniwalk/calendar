<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

use JuniWalk\Calendar\Source;

final class SourceNotEditableException extends CalendarException
{
	public static function fromSource(Source $source): static
	{
		return new static('Source "'.$source->getName().'" is not editable.');
	}
}
