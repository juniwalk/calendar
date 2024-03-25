<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Entity;

use DateTime;
use JuniWalk\Calendar\Config;
use JuniWalk\Calendar\Event;
use JuniWalk\Calendar\Exceptions\ConfigInvalidException;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Throwable;

class Parameters implements Config
{
	public ?string $themeSystem = 'bootstrap';
	public array|false|null $headerToolbar = false;
	public ?string $timeZone = 'Europe/Prague';
	public ?string $locale = null;
	public ?string $height = 'auto';
	public ?int $firstDay = 1;
	public ?bool $expandRows = true;
	public ?bool $nowIndicator = true;
	public ?bool $weekends = true;
	public ?int $longPressDelay = 200;
	public ?bool $lazyFetching = false;

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


	public function hasCustomToolbar(): bool
	{
		return !($this->headerToolbar ?? true);
	}


	public function isVisible(Event $event): bool
	{
		// TODO: Use business hours to check visibility
		return $event->getStart() < (new DateTime)->modify('18:00');
	}


	/**
	 * @throws ConfigInvalidException
	 */
	public function jsonSerialize(): array
	{
		try {
			$params = (new Processor)->process(
				self::createSchema()->castTo('array'),
				(array) $this,
			);

		} catch (Throwable $e) {
			throw ConfigInvalidException::fromException($e);
		}

		return array_filter($params, fn($v) => !is_null($v));
	}


	public function setParams(self|array $params): void
	{
		foreach ($params as $key => $value) {
			$this->$key = $value;
		}
	}


	public function setLocale(?string $locale): void
	{
		$this->locale = $locale;
	}
}
