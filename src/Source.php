<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTimeInterface as DateTime;
use DateTimeZone;
use JuniWalk\Calendar\Entity\Legend;
use Nette\Application\UI\SignalReceiver;
use Nette\Application\UI\StatePersistent;
use Nette\ComponentModel\IComponent as Component;

interface Source extends Component, SignalReceiver, StatePersistent
{
	public function setConfig(Config $config): void;

	/**
	 * @return Legend[]
	 */
	public function getLegend(): array;

	/**
	 * @return Event[]|EventProvider[]
	 */
	public function fetchEvents(DateTime $start, DateTime $end, DateTimeZone $timeZone): array;

	public function attachControls(Calendar $calendar): void;
	public function detachControls(Calendar $calendar): void;

	/**
	 * ComponentModel methods
	 */
	public function lookup(?string $type, bool $throw = true): ?Component;
	public function lookupPath(?string $type = null, bool $throw = true): ?string;
	public function monitor(string $type, ?callable $attached = null, ?callable $detached = null): void;
	public function unmonitor(string $type): void;
}
