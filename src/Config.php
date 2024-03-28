<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use JsonSerializable;

interface Config extends JsonSerializable
{
	public function setParam(string $param, mixed $value): void;
	public function getParam(string $param): mixed;

	public function isHeaderCustom(): bool;
	public function isEditable(): bool;

	public function isVisible(Event $event): bool;
	public function isOutOfBounds(Event $event): bool;
}
