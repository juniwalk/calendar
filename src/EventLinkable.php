<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use Nette\Application\UI\Link;

interface EventLinkable
{
	public function setUrl(string|Link $url): void;
}
