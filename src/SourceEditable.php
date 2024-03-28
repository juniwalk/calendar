<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTimeInterface as DateTime;

interface SourceEditable
{
	public function setEditable(bool $isEditable): void;

	public function eventDrop(int $id, DateTime $start, bool $allDay): void;
	public function eventResize(int $id, DateTime $end, bool $allDay): void;
}
