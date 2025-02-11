<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Utils;

use DateTime;
use DateTimeInterface;
use JuniWalk\Calendar\Enums\Steps;
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
	public static function normalize(?DateTimeInterface $date, Steps $steps = Steps::HalfHour): ?DateTime
	{
		if (!$date = static::clone($date)) {
			return null;
		}

		return $steps->normalize($date);
	}
}
