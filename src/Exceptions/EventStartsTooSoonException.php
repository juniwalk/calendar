<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

use JuniWalk\Calendar\Config;
use JuniWalk\Calendar\Event;
use JuniWalk\Utils\Format;

final class EventStartsTooSoonException extends EventInvalidException
{
	private ?Event $event = null;
	private ?string $time = null;

	public static function withEvent(Event $event, Config $config): static
	{
		$dow = $event->getStart()->format('N');
		$time = $config->findMinTime((int) $dow);

		$self = new static(Format::className($event).'#'.($event->id ?? 'unknown').' starts before the minimum allowed time of '.$time.'.');
		$self->event = $event;
		$self->time = $time;

		return $self;
	}


	public function getEvent(): ?Event
	{
		return $this->event;
	}


	public function getTime(): ?string
	{
		return $this->time;
	}
}
