<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

use JuniWalk\Calendar\Event;
use JuniWalk\Calendar\EventProvider;
use JuniWalk\Utils\Format;

final class EventEndsBeforeStartException extends EventInvalidException
{
	private Event|EventProvider $event;

	public static function withEvent(Event|EventProvider $event): static
	{
		$self = new static(Format::className($event).'#'.$event->getId().' ends before it starts.');
		$self->event = $event;

		return $self;
	}


	public function getEvent(): Event|EventProvider|null
	{
		return $this->event ?? null;
	}
}
