<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

use JuniWalk\Calendar\Source;
use Throwable;

final class EventNotFoundException extends CalendarException
{
	public static function fromSource(Source $source, int $id, ?Throwable $previous = null): static
	{
		return new static('Event #'.$id.' from source '.$source->getName().' was not found.', 500, $previous);
	}
}
