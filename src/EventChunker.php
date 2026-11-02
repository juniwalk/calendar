<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2026
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTimeImmutable;
use JuniWalk\Calendar\Exceptions\EventInvalidException;

use function ceil;
use function max;
use function min;

/**
 * @phpstan-import-type DayNumber from Enums\Day
 */
class EventChunker
{
	public function __construct(
		private readonly Config $config,
	) {
	}


	/**
	 * Split all-day events into chunks that fit into business hours.
	 *
	 * @param  Event[] $events
	 * @return Event[]
	 */
	public function chunk(array $events): array
	{
		$hours = $this->config->getBusinessHours();
		$result = [];

		foreach ($events as $event) {
			try {
				$this->config->checkOutOfBounds($event, true);

				if (!$event->isAllday()) {
					throw new EventInvalidException;
				}

			} catch (EventInvalidException) {
				$result[] = $event;
				continue;
			}

			$eventStart = $event->getStart();
			$eventEnd = $event->getEnd() ?? $eventStart->modify('+1 hour');

			foreach ($this->findDayChunks($eventStart, $eventEnd, $hours) as $chunk) {
				// ? Missing start, not valid
				if (!isset($chunk['start'])) {
					continue;
				}

				$item = clone $event;
				$item->setStart($chunk['start']);	// ?? $eventStart
				$item->setEnd($chunk['end'] ?? $eventEnd);
				$item->setGroupId($event->getId());
				$item->setAllDay(false);

				$result[] = $item;
			}
		}

		return $result;
	}


	/**
	 * Find the first chunk of each weekday the event overlaps with business hours.
	 * Moves day by day instead of stepping through every half hour slot, the slots
	 * are still aligned to the event start, so the result is the same.
	 *
	 * @param  array<int, array{start: ?string, end: ?string}> $hours
	 * @return array<int, array{start?: DateTimeImmutable, end?: DateTimeImmutable}>
	 */
	private function findDayChunks(DateTimeImmutable $start, DateTimeImmutable $end, array $hours): array
	{
		$from = $start->getTimestamp();
		$to = $end->getTimestamp();
		$chunks = [];
		$pending = 0;

		// ? Business hours of Sunday are stored as 0, so ISO day 7 never has any
		for ($dow = 1; $dow <= 7; $dow++) {
			$pending += !empty($hours[$dow]['start']) && !empty($hours[$dow]['end']);
		}

		// ? First slot at or after the timestamp, slots are 30 minutes apart
		$align = static fn(int $time): int => $from + (int) (ceil(($time - $from) / 1800) * 1800);
		$day = $start->setTime(0, 0);
		$first = true;

		while ($pending > 0 && $day->getTimestamp() < $to) {
			$current = $day;
			$day = $day->modify('+1 day');
			$dow = (int) $current->format('N');
			$dateStart = $hours[$dow]['start'] ?? null;
			$dateEnd = $hours[$dow]['end'] ?? null;
			$isFirst = $first;
			$first = false;

			if (!$dateStart || !$dateEnd) {
				continue;
			}

			$limit = min($to, $day->getTimestamp());
			$min = max($from, $current->getTimestamp());

			if (!isset($chunks[$dow]['start'])) {
				$slot = $isFirst ? $from : $align(max($min, $current->modify($dateStart)->getTimestamp()));

				if ($slot < $limit) {
					$chunks[$dow]['start'] = $start->setTimestamp($slot);
				}
			}

			if (!isset($chunks[$dow]['end'])) {
				$slot = $align(max($min, $current->modify($dateEnd)->getTimestamp()));

				if ($slot < $limit) {
					$chunks[$dow]['end'] = $start->setTimestamp($slot);
				}
			}

			if (isset($chunks[$dow]['start'], $chunks[$dow]['end'])) {
				$pending--;
			}
		}

		return $chunks;
	}
}
