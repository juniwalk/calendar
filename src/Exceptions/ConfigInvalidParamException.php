<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Exceptions;

use JuniWalk\Calendar\Config;

final class ConfigInvalidParamException extends CalendarException
{
	public static function fromParam(string $param, Config $config): static
	{
		return new static('Config parameter "'.$param.'" not found in config '.$config::class);
	}
}
