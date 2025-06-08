<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Entity;

use JuniWalk\Utils\Enums\Color;
use JuniWalk\Utils\Enums\Interfaces\LabeledEnum;
use JuniWalk\Utils\Html;

readonly class Legend
{
	public function __construct(
		public string $name,
		public ?string $icon,
		public Color $color,
	) {
	}


	public static function fromEnum(LabeledEnum $enum): self
	{
		return new self($enum->label(), $enum->icon(), $enum->color() ?? Color::Secondary);	// @phpstan-ignore-line
	}


	public function createBadge(): Html
	{
		/** @var Html */
		return Html::badge($this->name, $this->color, $this->icon);
	}
}
