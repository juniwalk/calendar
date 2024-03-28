<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

interface CalendarFactory
{
	public function create(?Config $config = null): Calendar;
}
