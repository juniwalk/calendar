<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Entity;

use DateTime;
use JuniWalk\Calendar\Calendar;
use JuniWalk\Calendar\Config;
use JuniWalk\Calendar\Event;
use JuniWalk\Calendar\Enums\Day;
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

class Parameters implements Config
{
	protected const Ignore = ['paddingStart', 'paddingEnd', 'autoRefresh', 'showDetails', 'responsive'];

	public ?string $themeSystem = 'bootstrap';
	public array|false|null $headerToolbar = false;
	public ?string $initialView = 'timeGridWeek';
	public ?string $initialDate = null;
	public ?string $timeZone = 'Europe/Prague';
	public ?string $locale = null;
	public ?string $height = 'auto';
	public Day|int|null $firstDay = Day::Monday;
	public array|bool $businessHours = false;
	public array $hiddenDays = [];
	public ?string $slotMinTime = null;
	public ?string $slotMaxTime = null;
	public int $paddingStart = 1;
	public int $paddingEnd = 1;
	public ?bool $expandRows = true;
	public ?bool $nowIndicator = true;
	public ?bool $weekends = true;
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
	public function checkOutOfBounds(Event $event, bool $strict = true): void
	{
		$start = $event->getStart();
		$end = $event->getEnd();

		if ($end && $end < $start) {
			throw EventEndsBeforeStartException::withEvent($event);
		}

		if ($strict === false) {
			return;
		}

		if ($start && ($this->startsTooSoon($start) || $this->endsTooLate($start))) {
			throw EventStartsTooSoonException::withTime($event, $this->calculateMinTime(false));
		}

		if ($end && ($this->endsTooLate($end) || $this->startsTooSoon($end))) {
			throw EventEndsTooLateException::withTime($event, $this->calculateMaxTime(false));
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


	/**
	 * @throws ConfigInvalidParamException
	 */
	public function setParams(self|array $params): void
	{
		Arrays::map($params, fn($v, $k) => $this->setParam($k, $v), false);
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


	public function loadState(Calendar $calendar, HttpRequest $request): void
	{
		$getCookie = function(string $name, mixed $default = null, string $type = 'bool') use ($calendar, $request) {
			$value = $request->getCookie($calendar->getName().'-'.$name);

			if ($value === null) {
				return $default;
			}

			return match ($type) {
				'bool' => (bool) $value,
				default => $value,
			};
		};

		$this->slotMinTime ??= $this->calculateMinTime();
		$this->slotMaxTime ??= $this->calculateMaxTime();

		$this->initialView = $getCookie('view', type: 'string');
		$this->initialDate = $getCookie('date', type: 'string');
		$this->autoRefresh = $getCookie('autoRefresh', $this->autoRefresh);
		$this->showDetails = $getCookie('showDetails', $this->showDetails);
		$this->responsive = $getCookie('responsive', $this->responsive);
		$this->editable = $getCookie('editable', $this->editable);
	}


	/**
	 * @throws ConfigInvalidException
	 */
	public function jsonSerialize(): array
	{
		try {
			$params = (new Processor)->process(
				self::createSchema()->castTo('array'),
				get_object_vars($this),
			);

		} catch (Throwable $e) {
			throw ConfigInvalidException::fromException($e);
		}

		return array_filter(
			array: $params,
			mode: ARRAY_FILTER_USE_BOTH,
			callback: fn($v, $k) => match (true) {
				in_array($k, self::Ignore) => false,
				default => !is_null($v),
			},
		);
	}


	public static function createSchema(): Schema
	{
		$day = Expect::anyOf(
			Expect::type(Day::class)->transform(fn($d) => $d->value),
			Expect::int()->min(0)->max(6),
		);

		$time = Expect::string()->pattern('\d{2}\:\d{2}(\d{2})?');
		$times = Expect::structure([
			'daysOfWeek'	=> Expect::listOf($day),
			'startTime'		=> $time,
			'endTime'		=> $time,
		])->castTo('array');

		return Expect::from(new self, [
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


	protected function calculateMinTime(bool $padding = true): ?string
	{
		$times = $this->businessHours();
		$times = array_filter(array_column($times, 'start'));

		if (empty($times)) {
			return null;
		}

		$date = new DateTime(min($times));

		if ($padding && ($date->format('G') - $this->paddingStart) >= 0) {
			$date->modify("-{$this->paddingStart} hours");
		}

		return $date->format('H:i');
	}


	protected function calculateMaxTime(bool $padding = true): ?string
	{
		$times = $this->businessHours();
		$times = array_filter(array_column($times, 'end'));

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


	protected function businessHours(): array
	{
		$times = Day::getBusinessHours();

		if (is_bool($this->businessHours)) {
			return $times;
		}

		$businessHours = $this->businessHours;

		if (isset($businessHours['daysOfWeek'])) {
			$businessHours = [$businessHours];
		}

		foreach ($businessHours as $businessHour) {
			foreach ($businessHour['daysOfWeek'] as $day) {
				$times[$day] = [
					'start' => $businessHour['startTime'],
					'end' => $businessHour['endTime'],
				];
			}
		}

		return $times;
	}


	protected function startsTooSoon(DateTime $start): bool
	{
		$businessHours = $this->businessHours();
		$dow = $start->format('N');

		if (!$time = ($businessHours[$dow]['start'] ?? null)) {
			return true;
		}

		$date = (clone $start)->modify($time);
		return $start < $date;
	}


	protected function endsTooLate(DateTime $end): bool
	{
		$businessHours = $this->businessHours();
		$dow = $end->format('N');

		if (!$time = ($businessHours[$dow]['end'] ?? null)) {
			return false;
		}

		$date = (clone $end)->modify($time);
		return $end > $date;
	}
}
