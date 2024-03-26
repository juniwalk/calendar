<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use JsonSerializable;

interface Config extends JsonSerializable
{
	public function isHeaderCustom(): bool;
	public function isVisible(Event $event): bool;
}
