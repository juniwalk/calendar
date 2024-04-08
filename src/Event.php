<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTimeInterface as DateTime;
use JsonSerializable;

interface Event extends JsonSerializable
{
	public function setSource(string $source): void;
	public function setStart(DateTime $start): void;
	public function getStart(): DateTime;
	public function setEnd(?DateTime $end): void;
	public function getEnd(): ?DateTime;
	public function setAllDay(bool $allDay): void;
	public function isAllDay(): bool;
}
