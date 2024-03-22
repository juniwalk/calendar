<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTimeInterface as DateTime;

interface Source
{
	public function setConfig(Config $config): void;
	public function getHandlers(): array;
	public function getLegend(): array;

	// public function setEditable(bool $isEditable): void;
	// public function drop(int $id, DateTime $start, bool $allDay): void;
	// public function resize(int $id, DateTime $end, bool $allDay): void;
	// public function createLink(Event $event, Calendar $calendar): void;

	public function createEvents(DateTime $start, DateTime $end): array;
}
