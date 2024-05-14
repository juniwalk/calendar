<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Utils;

use DateTime;
use DateTimeInterface;
use Nette\StaticClass;

final class Date
{
	use StaticClass;

	/**
	 * @return ($date is null ? null : DateTime)
	 */
	public static function clone(?DateTimeInterface $date): ?DateTime
	{
		if (is_null($date)) {
			return null;
		}

		return DateTime::createFromInterface($date);
	}


	/**
	 * @return ($date is null ? null : DateTime)
	 */
	public static function normalize(?DateTimeInterface $date): ?DateTime
	{
		if (!$date = static::clone($date)) {
			return null;
		}

		$m = (int) $date->format('i');
		$h = (int) $date->format('H');

		return match (true) {
			$m >= 45	=> $date->setTime($h+1,	00),
			$m >= 15	=> $date->setTime($h,	30),
			default		=> $date->setTime($h,	00),
		};
	}
}
