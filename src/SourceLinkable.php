<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

interface SourceLinkable
{
	public function eventLink(Event & EventLinkable $event, Calendar $calendar): string;
}
