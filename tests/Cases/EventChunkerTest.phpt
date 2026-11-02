<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2026
 * @license   MIT License
 */

namespace JuniWalk\Tests\Cases;

require __DIR__.'/../bootstrap.php';

use JuniWalk\Calendar\Config;
use JuniWalk\Calendar\Entity\Activity;
use JuniWalk\Calendar\EventChunker;
use JuniWalk\Calendar\Enums\Day;
use Tester\Assert;
use Tester\TestCase;

/**
 * @testCase
 */
final class EventChunkerTest extends TestCase
{
	public function testEventWithinSingleDay(): void
	{
		Assert::same(
			['1: 2025-01-06 09:00 - 2025-01-06 12:00'],
			$this->chunk(createEvent('2025-01-06 09:00', '2025-01-06 12:00')),
		);
	}


	public function testEventWithoutEndSpansOneHour(): void
	{
		Assert::same(
			['1: 2025-01-06 09:00 - 2025-01-06 10:00'],
			$this->chunk(createEvent('2025-01-06 09:00', null)),
		);
	}


	public function testMultiDayEventIsSplitPerDay(): void
	{
		Assert::same([
			'1: 2025-01-06 08:00 - 2025-01-06 16:00',
			'1: 2025-01-07 08:00 - 2025-01-07 16:00',
			'1: 2025-01-08 08:00 - 2025-01-08 16:00',
		], $this->chunk(createEvent('2025-01-06 08:00', '2025-01-08 16:00')));
	}


	public function testFirstAndLastDayKeepEventBounds(): void
	{
		Assert::same([
			'1: 2025-01-06 10:00 - 2025-01-06 16:00',
			'1: 2025-01-07 08:00 - 2025-01-07 16:00',
			'1: 2025-01-08 08:00 - 2025-01-08 12:00',
		], $this->chunk(createEvent('2025-01-06 10:00', '2025-01-08 12:00')));
	}


	public function testWeekendIsSkipped(): void
	{
		Assert::same([
			'1: 2025-01-03 14:00 - 2025-01-03 16:00',
			'1: 2025-01-06 08:00 - 2025-01-06 10:00',
		], $this->chunk(createEvent('2025-01-03 14:00', '2025-01-06 10:00')));
	}


	public function testDifferentHoursPerDay(): void
	{
		$config = new Config;
		$config->businessHours = [
			['daysOfWeek' => [Day::Monday], 'startTime' => '08:00', 'endTime' => '12:00'],
			['daysOfWeek' => [Day::Tuesday, 3], 'startTime' => '10:00', 'endTime' => '18:00'],
		];

		Assert::same([
			'1: 2025-01-06 09:00 - 2025-01-06 12:00',
			'1: 2025-01-07 10:00 - 2025-01-07 18:00',
			'1: 2025-01-08 10:00 - 2025-01-08 15:00',
		], $this->chunk(createEvent('2025-01-06 09:00', '2025-01-08 15:00'), $config));
	}


	public function testEventLongerThanWeekIsChunkedOncePerWeekday(): void
	{
		// ? Chunks are keyed by day of week, later weeks do not add new chunks
		Assert::same([
			'1: 2025-01-06 08:00 - 2025-01-06 16:00',
			'1: 2025-01-07 08:00 - 2025-01-07 16:00',
			'1: 2025-01-08 08:00 - 2025-01-08 16:00',
			'1: 2025-01-09 08:00 - 2025-01-09 16:00',
			'1: 2025-01-10 08:00 - 2025-01-10 16:00',
		], $this->chunk(createEvent('2025-01-06 08:00', '2025-03-18 12:00')));
	}


	public function testChunksReceiveGroupIdAndAreNotAllDay(): void
	{
		$events = $this->process([createEvent('2025-01-06 08:00', '2025-01-07 16:00', id: 7)]);

		Assert::count(2, $events);

		foreach ($events as $event) {
			Assert::same(7, $event->groupId);
			Assert::false($event->isAllDay());
		}
	}


	public function testChunksDoNotAffectOriginalEvent(): void
	{
		$original = createEvent('2025-01-06 08:00', '2025-01-07 16:00');
		$this->process([$original]);

		Assert::true($original->isAllDay());
		Assert::same('2025-01-06 08:00', $original->start->format('Y-m-d H:i'));
		Assert::same('2025-01-07 16:00', $original->getEnd()?->format('Y-m-d H:i'));
		Assert::false(isset($original->groupId));
	}


	public function testTimedEventIsKept(): void
	{
		Assert::same(
			['1: 2025-01-06 09:00 - 2025-01-06 11:00'],
			$this->chunk(createEvent('2025-01-06 09:00', '2025-01-06 11:00', allDay: false)),
		);
	}


	/**
	 * @dataProvider provideOutOfBoundsEvents
	 */
	public function testOutOfBoundsEventIsKept(string $start, ?string $end, string $expected): void
	{
		Assert::same([$expected], $this->chunk(createEvent($start, $end)));
	}


	/**
	 * @return array<string, array{string, ?string, string}>
	 */
	public static function provideOutOfBoundsEvents(): array
	{
		return [
			'starts before opening'		=> ['2025-01-06 06:00', '2025-01-07 12:00', '1: 2025-01-06 06:00 - 2025-01-07 12:00'],
			'starts after closing'		=> ['2025-01-06 18:00', '2025-01-07 12:00', '1: 2025-01-06 18:00 - 2025-01-07 12:00'],
			'ends after closing'		=> ['2025-01-06 09:00', '2025-01-06 18:00', '1: 2025-01-06 09:00 - 2025-01-06 18:00'],
			'ends before start'			=> ['2025-01-07 09:00', '2025-01-06 09:00', '1: 2025-01-07 09:00 - 2025-01-06 09:00'],
			'starts on weekend'			=> ['2025-01-04 10:00', '2025-01-06 10:00', '1: 2025-01-04 10:00 - 2025-01-06 10:00'],
			'ends on weekend'			=> ['2025-01-03 10:00', '2025-01-04 10:00', '1: 2025-01-03 10:00 - 2025-01-04 10:00'],
			'midnight to midnight'		=> ['2025-01-06 00:00', '2025-01-07 00:00', '1: 2025-01-06 00:00 - 2025-01-07 00:00'],
		];
	}


	public function testZeroLengthEventIsDropped(): void
	{
		Assert::same([], $this->chunk(createEvent('2025-01-06 09:00', '2025-01-06 09:00')));
	}


	public function testEventEndingAtOpeningTimeOfNextDay(): void
	{
		Assert::same(
			['1: 2025-01-06 14:00 - 2025-01-06 16:00'],
			$this->chunk(createEvent('2025-01-06 14:00', '2025-01-07 08:00')),
		);
	}


	public function testWithoutBusinessHoursEventIsKept(): void
	{
		Assert::same(
			['1: 2025-01-06 09:00 - 2025-01-08 12:00'],
			$this->chunk(createEvent('2025-01-06 09:00', '2025-01-08 12:00'), new Config),
		);
	}


	public function testMultipleEventsKeepOrder(): void
	{
		Assert::same([
			'2: 2025-01-07 09:00 - 2025-01-07 10:00',
			'1: 2025-01-06 08:00 - 2025-01-06 16:00',
			'1: 2025-01-07 08:00 - 2025-01-07 12:00',
			'3: 2025-01-04 10:00 - 2025-01-04 11:00',
		], $this->chunk(
			createEvent('2025-01-07 09:00', '2025-01-07 10:00', id: 2),
			null,
			createEvent('2025-01-06 08:00', '2025-01-07 12:00', id: 1),
			createEvent('2025-01-04 10:00', '2025-01-04 11:00', id: 3),
		));
	}


	/**
	 * @return string[]
	 */
	private function chunk(Activity $event, ?Config $config = null, Activity ...$more): array
	{
		return array_map(fn(Activity $e) => sprintf(
			'%s: %s - %s',
			$e->groupId ?? $e->id,
			$e->start->format('Y-m-d H:i'),
			$e->getEnd()?->format('Y-m-d H:i'),
		), $this->process([$event, ...$more], $config));
	}


	/**
	 * @param  Activity[] $events
	 * @return Activity[]
	 */
	private function process(array $events, ?Config $config = null): array
	{
		/** @var Activity[] */
		return (new EventChunker($config ?? createConfig()))->chunk($events);
	}
}

(new EventChunkerTest)->run();
