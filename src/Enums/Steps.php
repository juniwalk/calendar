<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2025
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Enums;

use DateTime;
use DateTimeInterface;

enum Steps
{
	case FiveMin;
	case HalfHour;


	public function normalize(DateTimeInterface $date): DateTime
	{
		$date = DateTime::createFromInterface($date);
		$h = (int) $date->format('H');
		$m = (int) $date->format('i');

		if ($this === self::FiveMin) {
			$x = $m % 5; $i = $m - $x;

			$date->setTime(... match (true) {
				$m >= 56	=> [$h + 1,	00],
				$x >= 1		=> [$h,		$i + 5],
				default		=> [$h,		$i],
			});
		}

		if ($this === self::HalfHour) {
			$date->setTime(... match (true) {
				$m >= 45	=> [$h + 1,	00],
				$m >= 15	=> [$h,		30],
				default		=> [$h,		00],
			});
		}

		return $date;
	}
}
