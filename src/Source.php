<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTimeInterface as DateTime;
use Nette\Application\UI\SignalReceiver;
use Nette\Application\UI\StatePersistent;
use Nette\ComponentModel\IComponent as Component;

interface Source extends Component, SignalReceiver, StatePersistent
{
	// public function setEditable(bool $isEditable): void;
	// public function createLink(Event $event, Calendar $calendar): void;

	public function setConfig(Config $config): void;
	public function getHandlers(): array;
	public function getLegend(): array;

	/**
	 * @return Event[]|EventProvider[]
	 */
	public function fetchEvents(DateTime $start, DateTime $end): array;

	public function attachControls(Calendar $calendar): void;
	public function detachControls(Calendar $calendar): void;
}
