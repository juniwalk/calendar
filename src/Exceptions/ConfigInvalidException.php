<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

use Throwable;

final class ConfigInvalidException extends CalendarException
{
	public static function fromException(Throwable $e): static
	{
		return new static('Given Calendar configuration is invalid.', 500, $e);
	}
}
