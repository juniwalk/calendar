<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Sources;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use JuniWalk\Calendar\Calendar;
use JuniWalk\Calendar\Config;
use JuniWalk\Calendar\Entity\Activity;
use JuniWalk\Calendar\Entity\Legend;
use JuniWalk\Calendar\Event;
use JuniWalk\Calendar\Source;
use Nette\Application\UI\Component;

final class CzechHolidaysSource extends Component implements Source
{
	public function setConfig(Config $config): void {}

	/**
	 * @return Legend[]
	 */
	public function getLegend(): array
	{
		return [];
	}


	/**
	 * @return Event[]
	 */
	public function fetchEvents(DateTimeInterface $start, DateTimeInterface $end, DateTimeZone $timeZone): array
	{
		$start = new DateTime($start->format(DateTime::ATOM));
		$events = [];

		foreach ($this->getHolidays($start) as $date => $name) {
			$date = (clone $start)->modify($date);

			if ($date < $start || $date >= $end) {
				continue;
			}

			$events[] = new Activity(params: [
				'id' => $date->format('U'),
				'title' => $name,
				'start' => $date,
				'display' => 'background',
				'allDay' => true,
				'editable' => false,
			]);
		}

		return $events;
	}


	public function attachControls(Calendar $calendar): void {}
	public function detachControls(Calendar $calendar): void {}


	/**
	 * @return array<string, string>
	 */
	private function getHolidays(DateTime $start): array
	{
		$year = (int) $start->format('Y');
		$easter = (clone $start)->modify($year.'-03-21')->modify(easter_days($year).' days');
		$goodFriday = (clone $easter)->modify('-2 day')->format('Y-m-d');
		$easterMonday = (clone $easter)->modify('+1 day')->format('Y-m-d');

		return [
			$year.'-01-01'	=> 'Nový rok',
			$goodFriday		=> 'Velký pátek',
			$easterMonday	=> 'Velikonoční pondělí',
			$year.'-05-01'	=> 'Svátek práce',
			$year.'-05-08'	=> 'Den vítězství',
			$year.'-07-05'	=> 'Den slovanských věrozvěstů Cyrila a Metoděje',
			$year.'-07-06'	=> 'Den upálení mistra Jana Husa',
			$year.'-09-28'	=> 'Den české státnosti',
			$year.'-10-28'	=> 'Den vzniku samostatného československého státu',
			$year.'-11-17'	=> 'Den boje za svobodu a demokracii',
			$year.'-12-24'	=> 'Štědrý den',
			$year.'-12-25'	=> '1. svátek vánoční',
			$year.'-12-26'	=> '2. svátek vánoční',
		];
	}
}
