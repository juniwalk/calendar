<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2026
 * @license   MIT License
 */

namespace JuniWalk\Calendar\Enums;

enum Capability: string
{
	case CalendarClick = 'calendar.click';
	case CalendarSettings = 'calendar.settings';
	case EventDetail = 'event.detail';
	case EventLink = 'event.link';
	case EventDrop = 'event.drop';
	case EventResize = 'event.resize';
}
