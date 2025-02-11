<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Enums;

use JuniWalk\Utils\Enums\Color;
use JuniWalk\Utils\Enums\Interfaces\LabeledEnum;
use JuniWalk\Utils\Enums\Traits\Labeled;

/**
 * @phpstan-type DayNumber int<0, 6>
 */
enum Day: int implements LabeledEnum
{
	use Labeled;

	case Monday = 1;
	case Tuesday = 2;
	case Wednesday = 3;
	case Thursday = 4;
	case Friday = 5;
	case Saturday = 6;
	case Sunday = 0;


	/**
	 * @return array<DayNumber, array{start: ?string, end: ?string}>
	 */
	public static function getBusinessHours(): array
	{
		$range = ['start' => null, 'end' => null];
		$dow = [];

		foreach (self::cases() as $case) {
			$dow[$case->value] = $range;
		}

		ksort($dow);
		return $dow;
	}


	public function label(): string
	{
		return match ($this) {
			self::Monday => 'calendar.day.monday',
			self::Tuesday => 'calendar.day.tuesday',
			self::Wednesday => 'calendar.day.wednesday',
			self::Thursday => 'calendar.day.thursday',
			self::Friday => 'calendar.day.friday',
			self::Saturday => 'calendar.day.saturday',
			self::Sunday => 'calendar.day.sunday',
		};
	}


	public function color(): Color
	{
		if (in_array($this->value, [0, 6])) {
			return Color::Secondary;
		}

		return Color::Info;
	}


	/**
	 * @param array<DayNumber, array{start: ?string, end: ?string}> $businessHours
	 */
	public function format(array $businessHours, ?string $closed = null): ?string
	{
		$start = $businessHours[$this->value]['start'] ?? null;
		$end = $businessHours[$this->value]['end'] ?? null;

		if (!$start || !$end) {
			return $closed;
		}

		return $start.' &ndash; '.$end;
	}
}
