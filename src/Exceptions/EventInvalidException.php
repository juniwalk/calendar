<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

use JuniWalk\Calendar\Event;

class EventInvalidException extends CalendarException
{
	public static function fromValue(mixed $event): static
	{
		$value = match ($type = gettype($event)) {
			'object' => $event::class,
			default => $type,
		};

		return new self('Event has to implement "'.Event::class.'", type of "'.$value.'" given.');
	}
}
