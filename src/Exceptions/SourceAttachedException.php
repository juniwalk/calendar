<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

use Nette\ComponentModel\IContainer as Control;

final class SourceAttachedException extends CalendarException
{
	public static function fromName(string $name, Control $parent): static
	{
		return new static('Source "'.$name.'" is already attached to '.$parent->getName());
	}
}
