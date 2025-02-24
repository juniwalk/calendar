<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use JsonSerializable;
use JuniWalk\Calendar\Enums\Theme;
use Nette\Http\IRequest as HttpRequest;

interface Config extends JsonSerializable
{
	public function checkOutOfBounds(Event $event, bool $strict = false): void;
	public function isVisible(Event $event): bool;
	public function isShowAllDayEvents(): bool;

	public function isViewsCollapsed(): bool;
	public function isAutoRefresh(): bool;
	public function isEditable(): bool;
	public function isHeaderCustom(): bool;
	public function isResponsive(): bool;
	public function isShowDetails(): bool;

	public function getTheme(): Theme;
	public function getParam(string $param, bool $throw = true): mixed;
	public function setParam(string $param, mixed $value): void;
	public function findMinTime(?int $dow, bool $padding = false): ?string;
	public function findMaxTime(?int $dow, bool $padding = false): ?string;

	/**
	 * @return array<int, array{start: ?string, end: ?string}>
	 */
	public function getBusinessHours(): array;

	public function loadState(Calendar $calendar, HttpRequest $request): void;
}
