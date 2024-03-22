<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Sources;

use DateTimeInterface as DateTime;
use JuniWalk\Calendar\Config;
use JuniWalk\Calendar\Entity\Event;
use JuniWalk\Calendar\Enums\Type;
use JuniWalk\Calendar\Source;

final class CzechHolidaysSource implements Source
{
	private Config $config;

	public function setConfig(Config $config): void
	{
		$this->config = $config;
	}


	public function getHandlers(): array
	{
		return [Type::Holiday];
	}


	public function getLegend(): array
	{
		return [];
	}


	public function createEvents(DateTime $start, DateTime $end): array
	{
		$events = [];

		foreach ($this->getHolidays($start) as $date => $name) {
			$date = (clone $start)->modify($date);

			if ($date < $start || $date >= $end) {
				continue;
			}

			$events[] = new Event([
				'id' => $date->format('U'),
				'type' => Type::Holiday,
				'title' => $name,
				'start' => $date,
				'display' => 'background',
				'allDay' => true,
				'editable' => false,
			]);
		}

		return $events;
	}


	private function getHolidays(DateTime $start): array
	{
		$year = (int) $start->format('Y');
		$easter = (clone $start)->modify($year.'-03-21')->modify(easter_days($year).' days');
		$goodFriday = (clone $easter)->modify('-2 day')->format('Y-m-d');
		$easterMonday = (clone $easter)->modify('+1 day')->format('Y-m-d');

		return [
			$year.'-01-01' => 'Nový rok',
			$goodFriday => 'Velký pátek',
			$easterMonday => 'Velikonoční pondělí',
			$year.'-05-01' => 'Svátek práce',
			$year.'-05-08' => 'Den vítězství',
			$year.'-07-05' => 'Den slovanských věrozvěstů Cyrila a Metoděje',
			$year.'-07-06' => 'Den upálení mistra Jana Husa',
			$year.'-09-28' => 'Den české státnosti',
			$year.'-10-28' => 'Den vzniku samostatného československého státu',
			$year.'-11-17' => 'Den boje za svobodu a demokracii',
			$year.'-12-24' => 'Štědrý den',
			$year.'-12-25' => '1. svátek vánoční',
			$year.'-12-26' => '2. svátek vánoční',
		];
	}
}
