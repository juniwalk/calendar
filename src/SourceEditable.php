<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTimeInterface;

interface SourceEditable
{
	public function setEditable(bool $isEditable): void;

	public function eventDrop(int $id, DateTimeInterface $start, bool $allDay): void;
	public function eventResize(int $id, DateTimeInterface $end, bool $allDay): void;
}
