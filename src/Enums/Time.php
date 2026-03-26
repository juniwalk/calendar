<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2025
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Enums;

use DateTimeImmutable;
use DateTimeInterface;

enum Time
{
	case FiveMin;
	case HalfHour;


	/**
	 * @return ($date is null ? null : DateTimeImmutable)
	 */
	public function normalize(?DateTimeInterface $date): ?DateTimeImmutable
	{
		if (empty($date)) {
			return null;
		}

		$date = DateTimeImmutable::createFromInterface($date);
		$h = (int) $date->format('H');
		$m = (int) $date->format('i');

		if ($this === self::FiveMin) {
			$x = $m % 5;
			$i = $m - $x;
		}

		return match ($this) {
			self::FiveMin => $date->setTime(... match (true) {
				$m >= 56	=> [$h + 1,	00],
				$x >= 1		=> [$h,		$i + 5],
				default		=> [$h,		$i],
			}),

			self::HalfHour => $date->setTime(... match (true) {
				$m >= 45	=> [$h + 1,	00],
				$m >= 15	=> [$h,		30],
				default		=> [$h,		00],
			}),
		};
	}
}
