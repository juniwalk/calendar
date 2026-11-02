<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

use JuniWalk\Calendar\Config;
use JuniWalk\Calendar\Entity\Activity;
use JuniWalk\Calendar\Enums\Day;
use Tester\Environment;

require __DIR__.'/../vendor/autoload.php';

Environment::setup();

/**
 * @param Day[] $days
 */
function createConfig(string $start = '08:00', string $end = '16:00', array $days = [Day::Monday, Day::Tuesday, Day::Wednesday, Day::Thursday, Day::Friday]): Config
{
	$config = new Config;
	$config->businessHours = [['daysOfWeek' => $days, 'startTime' => $start, 'endTime' => $end]];

	return $config;
}


function createEvent(string $start, ?string $end, bool $allDay = true, int $id = 1): Activity
{
	return new Activity(null, [
		'id' => $id,
		'title' => 'Event',
		'source' => 'test',
		'allDay' => $allDay,
		'start' => new DateTimeImmutable($start),
		'end' => $end ? new DateTimeImmutable($end) : null,
	]);
}
