<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use JuniWalk\Calendar\Config;
use JuniWalk\Calendar\Calendar;

interface CalendarFactory
{
	public function create(?Config $config = null): Calendar;
}
