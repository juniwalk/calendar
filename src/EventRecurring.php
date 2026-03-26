<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTimeImmutable;
use JuniWalk\Calendar\Enums\Day;	// ! Used in @phpstan-type

/**
 * @phpstan-import-type DayNumber from Day
 */
interface EventRecurring
{
	/** @var DayNumber[] */
	public array $daysOfWeek { get; }
	public ?DateTimeImmutable $startRecur { get; }
	public ?DateTimeImmutable $endRecur { get; }
	public string $startTime { get; }
	public string $endTime { get; }
}
