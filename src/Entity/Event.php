<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Entity;

use DateTime;
use JuniWalk\Calendar\Event as EventInterface;
use JuniWalk\Calendar\EventProvider;
use JuniWalk\Utils\Html;
use JuniWalk\Utils\Format;
use JuniWalk\Utils\Strings;

class Event implements EventInterface
{
	private readonly ?EventProvider $provider;

	public mixed $id;
	public mixed $groupId;
	public string $type;
	public bool $allDay;
	public DateTime $start;
	public ?DateTime $end;
	public string $title;
	public Html $titleHtml;

	// TODO: Create Popover entity
	public Html $content;
	public Html $label;

	public string $url;
	public array $classNames;
	public bool $editable;
	public string $display;

	// Recurrence
	public array $daysOfWeek;
	public ?DateTime $startRecur;
	public ?DateTime $endRecur;
	public string $startTime;
	public string $endTime;

	public function __construct(array $params, ?EventProvider $provider = null)
	{
		$this->provider = $provider;

		foreach ($params as $key => $value) {
			if (!property_exists($this, $key)) {
				continue;
			}

			if ($value instanceof DateTime) {
				$value = clone $value;
			}

			$this->$key = $value;
		}

		if (isset($this->end) && $this->allDay && $this->end->format('H:i') <> '00:00') {
			$this->end->modify('midnight next day');
		}
	}


	public function jsonSerialize(): array
	{
		$params = get_object_vars($this);
		$params['title'] = Strings::replace($params['title'], '/\r?\n/i', ' ');

		foreach ($params as $key => $value) {
			$params[$key] = match (true) {
				$value instanceof EventProvider => null,
				default => Format::scalarize($value) ?? $value,
			};
		}

		return array_filter($params);
	}


	public function getProvider(): ?EventProvider
	{
		return $this->provider;
	}


	public function getStart(): DateTime
	{
		return $this->start;
	}


	public function getEnd(): ?DateTime
	{
		return $this->end ?? null;
	}


	public function setAllDay(bool $allDay): void
	{
		$this->allDay = $allDay;
	}


	public function isAllDay(): bool
	{
		return $this->allDay;
	}


	public function createTime(bool $fullDate = null): Html
	{
		$fullDate ??= $this->allDay || isset($this->groupId);
		$format = $fullDate ? 'j.n. G:i' : 'G:i';
		$time = $this->provider?->getStart()->format($format);

		if ($end = $this->provider?->getEnd()) {
			$time .= ' - '.$end->format($format);
		}

		return Html::el('small', $time);
	}
}
