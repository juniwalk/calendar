<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Entity;

use DateTime;
use DateTimeInterface;
use JuniWalk\Calendar\Calendar;
use JuniWalk\Calendar\Config;
use JuniWalk\Calendar\Event;
use JuniWalk\Calendar\Enums\Day;
use JuniWalk\Calendar\Enums\Theme;
use JuniWalk\Calendar\Enums\View;
use JuniWalk\Calendar\Exceptions\ConfigInvalidException;
use JuniWalk\Calendar\Exceptions\ConfigInvalidParamException;
use JuniWalk\Calendar\Exceptions\EventEndsBeforeStartException;
use JuniWalk\Calendar\Exceptions\EventEndsTooLateException;
use JuniWalk\Calendar\Exceptions\EventStartsTooSoonException;
use JuniWalk\Calendar\Exceptions\EventUnableToDisplayException;
use JuniWalk\Utils\Arrays;
use Nette\Http\IRequest as HttpRequest;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Throwable;

/**
 * @phpstan-import-type DayNumber from Day
 * @phpstan-type BusinessHour array{daysOfWeek: list<Day|DayNumber>, startTime: string, endTime: string}
 */
class Options implements Config
{
	protected const Ignore = ['paddingStart', 'paddingEnd', 'autoRefresh', 'showAllDayEvents', 'viewsCollapsed', 'showDetails', 'responsive'];

	public Theme|string|null $themeSystem = Theme::Bootstrap4;

	/** @var array<string, string[]> */
	public array|false|null $headerToolbar = false;

	public View|string|null $initialView = View::Week;
	public ?string $initialDate = null;
	public ?string $timeZone = 'Europe/Prague';
	public ?string $locale = null;
	public ?string $height = 'auto';

	/** @var DayNumber */
	public Day|int|null $firstDay = Day::Monday;

	/** @var BusinessHour|BusinessHour[]|bool */
	public array|bool $businessHours = false;

	/** @var Day[]|DayNumber[] */
	public array $hiddenDays = [];

	public ?string $slotMinTime = null;
	public ?string $slotMaxTime = null;
	public int $paddingStart = 1;
	public int $paddingEnd = 1;
	public ?bool $expandRows = true;
	public ?bool $nowIndicator = true;
	public ?bool $weekends = true;
	public bool $forceStrictBounds = false;
	public bool $showAllDayEvents = false;
	public bool $viewsCollapsed = false;
	public bool $autoRefresh = true;
	public ?bool $editable = null;
	public bool $showDetails = true;
	public bool $responsive = true;
	public ?int $longPressDelay = 200;
	public ?bool $lazyFetching = false;


	/**
	 * @throws EventEndsBeforeStartException
	 * @throws EventEndsTooLateException
	 * @throws EventStartsTooSoonException
	 * @throws EventUnableToDisplayException
	 */
	public function checkOutOfBounds(Event $event, bool $strict = false): void
	{
		$start = $event->getStart();
		$end = $event->getEnd();

		if ($end && $end < $start) {
			throw EventEndsBeforeStartException::withEvent($event);
		}

		if (!$strict && !$this->forceStrictBounds) {
			return;
		}

		if ($this->startsTooSoon($start) || $this->endsTooLate($start)) {
			throw EventStartsTooSoonException::withEvent($event, $this);
		}

		if ($end && ($this->endsTooLate($end) || $this->startsTooSoon($end))) {
			throw EventEndsTooLateException::withEvent($event, $this);
		}

		if (!$this->isVisible($event)) {
			throw EventUnableToDisplayException::withEvent($event);
		}
	}


	public function isVisible(Event $event): bool
	{
		$start = $event->getStart();
		$end = $event->getEnd();

		if (in_array($start->format('N'), $this->hiddenDays)) {
			return false;
		}

		if ($this->slotMaxTime && $start->format('Hi') >= strtr($this->slotMaxTime, [':' => ''])) {
			return false;
		}

		if ($this->slotMinTime && $end && $end->format('Hi') <= strtr($this->slotMinTime, [':' => ''])) {
			return false;
		}

		return true;
	}


	public function isShowAllDayEvents(): bool
	{
		return $this->showAllDayEvents;
	}


	public function isViewsCollapsed(): bool
	{
		return $this->viewsCollapsed;
	}


	public function isAutoRefresh(): bool
	{
		return $this->autoRefresh;
	}


	public function isHeaderCustom(): bool
	{
		return !($this->headerToolbar ?? true);
	}


	public function isEditable(): bool
	{
		return (bool) $this->editable;
	}


	public function isResponsive(): bool
	{
		return $this->responsive;
	}


	public function isShowDetails(): bool
	{
		return $this->showDetails;
	}


	public function setLocale(?string $locale): void
	{
		$this->locale = $locale;
	}


	public function getTheme(): Theme
	{
		return Theme::make($this->themeSystem, false) ?? Theme::Bootstrap4;
	}


	/**
	 * @param array<string, mixed> $params
	 * @throws ConfigInvalidParamException
	 */
	public function setParams(self|array $params): void
	{
		Arrays::map((array) $params, fn($v, $k) => $this->setParam($k, $v), false);	// @phpstan-ignore-line
	}


	/**
	 * @throws ConfigInvalidParamException
	 */
	public function setParam(string $param, mixed $value): void
	{
		if (!property_exists($this, $param)) {
			throw ConfigInvalidParamException::fromParam($param, $this);
		}

		$this->$param = $value;
	}


	/**
	 * @throws ConfigInvalidParamException
	 */
	public function getParam(string $param, bool $throw = true): mixed
	{
		if ($throw && !property_exists($this, $param)) {
			throw ConfigInvalidParamException::fromParam($param, $this);
		}

		return $this->$param ?? null;
	}


	public function findMinTime(?int $dow, bool $padding = false): ?string
	{
		$times = $this->getBusinessHours();
		$times = array_filter(match ($dow) {
			null => array_filter(array_column($times, 'start')),
			default => [$times[$dow]['start'] ?? null],
		});

		if (empty($times)) {
			return null;
		}

		$date = new DateTime(min($times));

		if ($padding && ($date->format('G') - $this->paddingStart) >= 0) {
			$date->modify("-{$this->paddingStart} hours");
		}

		return $date->format('H:i');
	}


	public function findMaxTime(?int $dow, bool $padding = false): ?string
	{
		$times = $this->getBusinessHours();
		$times = array_filter(match ($dow) {
			null => array_filter(array_column($times, 'end')),
			default => [$times[$dow]['end'] ?? null],
		});

		if (empty($times)) {
			return null;
		}

		$date = new DateTime(max($times));

		if ($padding && ($date->format('G') + $this->paddingEnd) <= 24) {
			$date->modify("+{$this->paddingEnd} hours");
		}

		if ($date->format('H:i') === '00:00') {
			return null;
		}

		return $date->format('H:i');
	}


	/**
	 * @return array<DayNumber, array{start: ?string, end: ?string}>
	 */
	public function getBusinessHours(): array
	{
		$businessHours = $this->businessHours;
		$times = Day::getBusinessHours();

		if (is_bool($businessHours)) {
			return $times;
		}

		if (isset($businessHours['daysOfWeek'])) {
			$businessHours = [$businessHours];
		}

		/** @var BusinessHour $businessHour */
		foreach ($businessHours as $businessHour) {
			$startTime = $businessHour['startTime'];
			$endTime = $businessHour['endTime'];

			foreach ($businessHour['daysOfWeek'] as $day) {
				if ($day instanceof Day) {
					$day = $day->value;
				}

				$times[$day]['start'] = $startTime;
				$times[$day]['end'] = $endTime;
			}
		}

		return $times;
	}


	public function loadState(Calendar $calendar, HttpRequest $request): void
	{
		$getCookie = fn(string $name, mixed $default = null) => $request->getCookie($calendar->getName().'-'.$name) ?? $default;

		if ($initialView = $getCookie('view')) {
			/** @var scalar $initialView */
			$this->initialView = (string) $initialView;
		}

		if ($initialDate = $getCookie('date')) {
			/** @var scalar $initialDate */
			$this->initialDate = (string) $initialDate;
		}

		if ($editable = $getCookie('editable', $this->editable)) {
			$this->editable = (bool) $editable;
		}

		$this->autoRefresh = (bool) $getCookie('autoRefresh', $this->autoRefresh);
		$this->showDetails = (bool) $getCookie('showDetails', $this->showDetails);
		$this->responsive = (bool) $getCookie('responsive', $this->responsive);
		$this->slotMinTime ??= $this->findMinTime(null, true);
		$this->slotMaxTime ??= $this->findMaxTime(null, true);
	}


	/**
	 * @return array<string, mixed>
	 * @throws ConfigInvalidException
	 */
	public function jsonSerialize(): array
	{
		try {
			/** @var array<string, mixed> */
			$params = (new Processor)->process(
				self::createSchema()->castTo('array'),	// @phpstan-ignore-line
				get_object_vars($this),
			);

		} catch (Throwable $e) {
			throw ConfigInvalidException::fromException($e);
		}

		$skipIgnored = fn($v, $k) => match (true) {
			in_array($k, self::Ignore) => false,
			default => !is_null($v),
		};

		return array_filter($params, $skipIgnored, ARRAY_FILTER_USE_BOTH);
	}


	public static function createSchema(): Schema
	{
		$day = Expect::anyOf(
			Expect::type(Day::class)->transform(fn($i) => $i->value),
			Expect::int()->min(0)->max(6),
		);

		$time = Expect::string()->pattern('\d{2}\:\d{2}(\d{2})?');
		$times = Expect::structure([
			'daysOfWeek'	=> Expect::listOf($day),
			'startTime'		=> $time,
			'endTime'		=> $time,
		])->castTo('array');

		return Expect::from(new self, [
			'themeSystem'	=> Expect::anyOf(
				Expect::type(Theme::class)->transform(fn($i) => $i->value),
				Expect::string(),
				Expect::null(),
			),
			'initialView'	=> Expect::anyOf(
				Expect::type(View::class)->transform(fn($i) => $i->value),
				Expect::string(),
				Expect::null(),
			),
			'firstDay'		=> (clone $day)->nullable(),
			'hiddenDays'	=> Expect::listOf($day),
			'slotMinTime'	=> (clone $time)->nullable(),
			'slotMaxTime'	=> (clone $time)->nullable(),
			'businessHours'	=> Expect::anyOf(Expect::bool(), $times, Expect::listOf($times))->default(false),
			'headerToolbar'	=> Expect::anyOf(null, false, Expect::structure([
				'start'		=> Expect::string(),
				'center'	=> Expect::string(),
				'end'		=> Expect::string(),
			])),
		]);
	}


	protected function startsTooSoon(DateTimeInterface $date): bool
	{
		$dow = (int) $date->format('N');

		if (!$date instanceof DateTime ||
			!$time = $this->findMinTime($dow)) {
			return true;
		}

		return $date < (clone $date)->modify($time);
	}


	protected function endsTooLate(DateTimeInterface $date): bool
	{
		$dow = (int) $date->format('N');

		if (!$date instanceof DateTime ||
			!$time = $this->findMaxTime($dow)) {
			return false;
		}

		return $date > (clone $date)->modify($time);
	}
}
