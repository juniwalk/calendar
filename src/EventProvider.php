<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use Nette\Localization\Translator;

interface EventProvider
{
	public function createEvent(Translator $translator): Event;
}
