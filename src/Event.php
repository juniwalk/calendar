<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTimeInterface;
use DateTimeImmutable;
use Nette\Utils\Html;
use JsonSerializable;

interface Event extends JsonSerializable
{
	public int|string|null $id { get; }
	public int|string|null $groupId { get; }
	public string $source { get; }
	public bool $allDay { get; }
	public DateTimeImmutable $start { get; }
	public ?DateTimeImmutable $end { get; }
	public string $title { get; }
	public Html $titleHtml { get; }
	/** @var string[] */
	public array $classNames { get; }
	public bool $editable { get; }
	public string $display { get; }


	public function getId(): int|string|null;
	public function setGroupId(int|string|null $groupId): void;
	public function setSource(string $source): void;
	public function getSource(): string;
	public function setStart(DateTimeInterface $start): void;
	public function getStart(): DateTimeImmutable;
	public function setEnd(?DateTimeInterface $end): void;
	public function getEnd(): ?DateTimeImmutable;
	public function setAllDay(bool $allDay): void;
	public function isAllDay(): bool;
}
