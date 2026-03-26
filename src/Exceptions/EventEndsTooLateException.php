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

final class EventEndsTooLateException extends EventInvalidException
{
	private Event|EventProvider $event;
	private string $time;

	public static function withEvent(Event|EventProvider $event, Config $config): static
	{
		$dow = $event->getEnd()?->format('N');
		$time = $config->findMaxTime(match (true) {
			isset($dow) => (int) $dow,
			default => null,
		});

		$self = new static(Format::className($event).'#'.($event->getId() ?? 'unknown').' ends after the maximum allowed time of '.$time.'.');
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
