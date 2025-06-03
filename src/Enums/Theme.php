<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Enums;

use JuniWalk\Utils\Enums\Color;
use JuniWalk\Utils\Enums\Interfaces\LabeledEnum;
use JuniWalk\Utils\Enums\Traits\Labeled;

enum Theme: string implements LabeledEnum
{
	use Labeled;

	case Bootstrap4 = 'bootstrap';
	case Bootstrap5 = 'bootstrap5';
	// case Standard = 'standard';


	public function label(): string
	{
		return match ($this) {
			self::Bootstrap4 => 'Bootstrap 4',
			self::Bootstrap5 => 'Bootstrap 5',
		};
	}


	public function file(): string
	{
		return match ($this) {
			self::Bootstrap4 => 'calendar.bs4.latte',
			self::Bootstrap5 => 'calendar.bs5.latte',
		};
	}
}
