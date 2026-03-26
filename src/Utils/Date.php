<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Utils;

use DateTimeImmutable;
use DateTimeInterface;
use JuniWalk\Calendar\Enums\Steps;
use Nette\StaticClass;

final class Date
{
	use StaticClass;

	/**
	 * @return ($date is null ? null : DateTimeImmutable)
	 */
	public static function clone(?DateTimeInterface $date): ?DateTimeImmutable
	{
		if (is_null($date)) {
			return null;
		}

		return DateTimeImmutable::createFromInterface($date);
	}


	/**
	 * @return ($date is null ? null : DateTimeImmutable)
	 */
	public static function normalize(?DateTimeInterface $date, Steps $steps = Steps::HalfHour): ?DateTimeImmutable
	{
		if (!$date = static::clone($date)) {
			return null;
		}

		return $steps->normalize($date);
	}
}
