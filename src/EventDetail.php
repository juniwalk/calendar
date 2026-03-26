<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use Nette\Utils\Html;

interface EventDetail
{
	public Html $content { get; }
	public Html $label { get; }
}
