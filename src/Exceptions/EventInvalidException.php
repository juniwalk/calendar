<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

use JuniWalk\Calendar\Event;
use Throwable;

class EventInvalidException extends CalendarException
{
	private mixed $event = null;

	public static function fromEvent(Event $event, ?Throwable $previous = null): self
	{
		$self = new self('Event "'.$event::class.'" failed schema validation.', 500, $previous);
		$self->event = $event;

		return $self;
	}


	public static function fromValue(mixed $event): self
	{
		$value = gettype($event);

		if (is_object($event)) {
			$value = $event::class;
		}

		$self = new self('Event has to implement "'.Event::class.'", type of "'.$value.'" given.');
		$self->event = $event;

		return $self;
	}


	public function getEvent(): mixed
	{
		return $this->event;
	}
}
