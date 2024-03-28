<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTimeInterface as DateTime;
use JsonSerializable;
use Stringable;

interface Event extends JsonSerializable, Stringable
{
	public function getStart(): DateTime;
	public function getEnd(): ?DateTime;
	public function setAllDay(bool $allDay): void;
	public function isAllDay(): bool;
}
