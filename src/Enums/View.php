<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Enums;

use JuniWalk\Utils\Enums\Color;
use JuniWalk\Utils\Enums\Interfaces\LabeledEnum;
use JuniWalk\Utils\Enums\Traits\Labeled;

enum View: string implements LabeledEnum
{
	use Labeled;

	case Month = 'dayGridMonth';
	case Week = 'timeGridWeek';
	case Day = 'timeGridDay';


	public function label(): string
	{
		return match($this) {
			self::Month => 'calendar.toolbar.view.month',
			self::Week => 'calendar.toolbar.view.week',
			self::Day => 'calendar.toolbar.view.day',
		};
	}


	public function color(): Color
	{
		return Color::Primary;
	}


	public function icon(): ?string
	{
		return match($this) {
			self::Month => 'fa-solid fa-calendar-days',
			self::Week => 'fa-solid fa-calendar-week',
			self::Day => 'fa-solid fa-calendar-day',
		};
	}
}
