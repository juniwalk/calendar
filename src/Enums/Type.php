<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Enums;

use JuniWalk\Utils\Enums\Color;
use JuniWalk\Utils\Enums\Interfaces\LabeledEnum;
use JuniWalk\Utils\Enums\Traits\Labeled;

enum Type: string implements LabeledEnum
{
	use Labeled;

	case Event = 'event';
	case Absence = 'absence';
	case Vacation = 'vacation';
	case Holiday = 'holiday';
	case Note = 'note';


	public function label(): string
	{
		return $this->value;
	}


	public function icon(): string
	{
		return match($this) {
			self::Event => 'fa-calendar-check',
			self::Absence => 'fa-syringe',
			self::Vacation => 'fa-umbrella-beach',
			self::Holiday => 'fa-gifts',
			self::Note => 'fa-sticky-note',
		};
	}


	public function color(): Color
	{
		return Color::Secondary;
	}
}
