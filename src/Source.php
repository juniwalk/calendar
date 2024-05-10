<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTime;
use DateTimeZone;
use JuniWalk\Calendar\Entity\Legend;
use Nette\Application\UI\SignalReceiver;
use Nette\Application\UI\StatePersistent;
use Nette\Application\UI\Component;
use Nette\ComponentModel\IComponent as ComponentInterface;

/**
 * @phpstan-require-extends Component
 */
interface Source extends ComponentInterface, SignalReceiver, StatePersistent
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
}
