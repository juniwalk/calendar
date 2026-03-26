<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

use JuniWalk\Calendar\Config;
use JuniWalk\Calendar\Event;
use JuniWalk\Calendar\EventProvider;
use JuniWalk\Utils\Format;

final class EventStartsTooSoonException extends EventInvalidException
{
	private Event|EventProvider $event;
	private string $time;

	public static function withEvent(Event|EventProvider $event, Config $config): static
	{
		$dow = $event->getStart()->format('N');
		$time = $config->findMinTime((int) $dow);

		$self = new static(Format::className($event).'#'.($event->getId() ?? 'unknown').' starts before the minimum allowed time of '.$time.'.');
		$self->event = $event;

		if (is_string($time)) {
			$self->time = $time;
		}

		return $self;
	}


	public function getEvent(): Event|EventProvider|null
	{
		return $this->event ?? null;
	}


	public function getTime(): ?string
	{
		return $this->time ?? null;
	}
}
