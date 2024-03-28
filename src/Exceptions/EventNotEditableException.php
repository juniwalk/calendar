<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

use JuniWalk\Calendar\Event;

final class EventNotEditableException extends CalendarException
{
	public static function fromEvent(Event $event): static
	{
		return new static('Event '.$event::class.' is not editable.');
	}
}
