<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Entity;

use DateTime;
use JuniWalk\Calendar\Enums\Day;	// ! Used in @phpstan-type
use JuniWalk\Calendar\Event;
use JuniWalk\Calendar\EventDetail;
use JuniWalk\Calendar\EventLinkable;
use JuniWalk\Calendar\EventProvider;
use JuniWalk\Calendar\EventRecurring;
use JuniWalk\Calendar\Utils\Date;
use JuniWalk\Utils\Html;
use JuniWalk\Utils\Format;
use JuniWalk\Utils\Strings;
use Nette\Application\UI\Link;

/**
 * @phpstan-import-type DayNumber from Day
 */
class Activity implements Event, EventDetail, EventLinkable, EventRecurring
{
	// Event
	public int|string|null $id;
	public int|string|null $groupId;
	public string $source;
	public bool $allDay;
	public DateTime $start;
	public ?DateTime $end;
	public string $title;
	public Html $titleHtml;
	/** @var string[] */
	public array $classNames;
	public bool $editable;
	public string $display;

	// EventLinkable
	public string|Link $url;

	// EventDetail
	public Html $content;
	public Html $label;

	// EventRecurring
	/** @var DayNumber[] */
	public array $daysOfWeek;
	public ?DateTime $startRecur;
	public ?DateTime $endRecur;
	public string $startTime;
	public string $endTime;

	/**
	 * @param array<string, mixed> $params
	 */
	public function __construct(
		private readonly ?EventProvider $provider = null,
		array $params = [],
	) {
		$this->setParams($params);
	}


	public function getId(): int|string|null
	{
		return $this->id;
	}


	public function setGroupId(int|string|null $groupId): void
	{
		$this->groupId = $groupId;
	}


	public function setSource(string $source): void
	{
		$this->source = $source;
	}


	public function getSource(): string
	{
		return $this->source;
	}


	public function setStart(DateTime $start): void
	{
		$this->start = Date::normalize($start);
	}


	public function getStart(): DateTime
	{
		return $this->start;
	}


	public function setEnd(?DateTime $end): void
	{
		$this->end = Date::normalize($end);
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


	public function setUrl(string|Link $url): void
	{
		$this->url = $url;
	}


	public function createTime(?bool $fullDate = null): Html
	{
		$fullDate ??= $this->allDay || isset($this->groupId);
		$format = $fullDate ? 'j.n. G:i' : 'G:i';
		$time = $this->provider?->getStart()->format($format);

		if ($end = $this->provider?->getEnd()) {
			$time .= ' - '.$end->format($format);
		}

		return Html::el('small', $time);
	}


	public function getProvider(): ?EventProvider
	{
		return $this->provider;
	}


	/**
	 * @param array<string, mixed> $params
	 */
	public function setParams(array $params): void
	{
		foreach ($params as $key => $value) {
			if (!property_exists($this, $key)) {
				continue;
			}

			if ($value instanceof DateTime) {
				$value = clone $value;
			}

			$this->$key = $value;
		}
	}


	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array
	{
		$params = get_object_vars($this);
		$params['title'] = Strings::replace($this->title, '/\r?\n/i', ' ');

		foreach ($params as $key => $value) {
			$params[$key] = match (true) {
				$value instanceof EventProvider => null,
				default => Format::serializable($value) ?? $value,
			};
		}

		return array_filter($params, fn($v) => !is_null($v));
	}
}
