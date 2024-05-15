<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use Nette\Application\UI\Link;

interface SourceLinkable
{
	public function eventLink(Event & EventLinkable $event, Calendar $calendar): string|Link;
}
