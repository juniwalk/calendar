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
use JuniWalk\Calendar\Exceptions\ConfigInvalidException;
use JuniWalk\Calendar\Exceptions\ConfigInvalidParamException;
use JuniWalk\Utils\Arrays;
use Nette\Http\IRequest as HttpRequest;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Throwable;

class Parameters implements Config
{
	private const Ignore = ['autoRefresh', 'showDetails', 'responsive'];

	public ?string $themeSystem = 'bootstrap';
	public array|false|null $headerToolbar = false;
	public ?string $initialView = 'timeGridWeek';
	public ?string $initialDate = null;
	public ?string $timeZone = 'Europe/Prague';
	public ?string $locale = null;
	public ?string $height = 'auto';
	public ?int $firstDay = 1;
	public ?bool $expandRows = true;
	public ?bool $nowIndicator = true;
	public ?bool $weekends = true;
	public bool $autoRefresh = true;
	public ?bool $editable = null;
	public bool $showDetails = true;
	public bool $responsive = true;
	public ?int $longPressDelay = 200;
	public ?bool $lazyFetching = false;


	public function isVisible(Event $event): bool
	{
		// TODO: Use business hours to check visibility
		return $event->getStart() < (new DateTime)->modify('18:00');
	}


	public function isOutOfBounds(Event $event): bool
	{
		// TODO: Use business hours to check strict hours (bounds)
		return false;
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
		Arrays::map($params, fn($value, $param) => $this->setParam($param, $value));
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
		$this->initialView = $request->getCookie($calendar->getName().'-view');
		$this->initialDate = $request->getCookie($calendar->getName().'-date');

		$this->autoRefresh = (bool) $request->getCookie($calendar->getName().'-autoRefresh') ?? $this->autoRefresh;
		$this->showDetails = (bool) $request->getCookie($calendar->getName().'-showDetails') ?? $this->showDetails;
		$this->responsive = (bool) $request->getCookie($calendar->getName().'-responsive') ?? $this->responsive;

		$editable = $request->getCookie($calendar->getName().'-editable');
		$this->editable = $editable <> null ? (bool) $editable : null;
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
		return Expect::from(new self, [
			'headerToolbar'	=> Expect::anyOf(null, false, Expect::structure([
				'start'		=> Expect::string(),
				'center'	=> Expect::string(),
				'end'		=> Expect::string(),
			])),
		]);
	}
}
