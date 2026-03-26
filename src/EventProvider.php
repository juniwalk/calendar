<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTimeImmutable;
use Nette\Localization\Translator;

interface EventProvider
{
	public function getId(): int|string|null;
	public function getSource(): string;
	public function getStart(): DateTimeImmutable;
	public function getEnd(): ?DateTimeImmutable;
	public function isAllDay(): bool;

	public function createEvent(Translator $translator): Event;
}
