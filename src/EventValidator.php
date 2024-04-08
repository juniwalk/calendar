<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTime;
use JuniWalk\Calendar\Enums\Day;
use JuniWalk\Calendar\Exceptions\EventInvalidException;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nette\Utils\Html;
use Throwable;

class EventValidator
{
	private readonly Processor $processor;
	private array $schema = [];

	public function __construct()
	{
		$this->processor = new Processor;
	}


	/**
	 * @throws EventInvalidException
	 */
	public function validate(Event $event, Source $source): object
	{
		$this->schema[$event::class] ??= $this->createSchema($event);

		try {
			$end = $event->getEnd();

			if ($end && $event->isAllDay() && $end->format('H:i') <> '00:00') {
				$event->setEnd($end->modify('midnight next day'));
			}

			$event = $this->processor->process(
				$this->schema[$event::class],
				$event->jsonSerialize(),
			);

			if ($event->source <> $source->getName()) {
				throw new EventInvalidException('Event\'s source property has to match its source name.');
			}

			return $event;

		} catch (Throwable $e) {
			throw EventInvalidException::fromEvent($event, $e);
		}
	}


	// TODO: Use Activity as Schema source as in Parameters & Config?
	private function createSchema(Event $event): Schema
	{
		$day = Expect::anyOf(
			Expect::type(Day::class)->transform(fn($d) => $d->value),
			Expect::int()->min(0)->max(6),
		);

		$date = Expect::anyOf(
			Expect::type(DateTime::class)->transform(fn($d) => $d->format('c')),
			Expect::string(),
		);

		$html = Expect::anyOf(
			Expect::type(Html::class)->transform(fn($d) => $d->render()),
			Expect::string(),
			Expect::null(),
		);

		$schema = [
			'id'			=> Expect::scalar(),
			'groupId'		=> Expect::scalar(),
			'source'		=> Expect::string()->required(),
			'allDay'		=> Expect::bool(),
			'start'			=> (clone $date)->required(),
			'end'			=> (clone $date)->nullable(),
			'title'			=> Expect::string()->required(),
			'titleHtml'		=> $html,
			'classNames'	=> Expect::listOf(Expect::string()),
			'editable'		=> Expect::bool(),
			'display'		=> Expect::string(),
		];

		if ($event instanceof EventDetail) {
			$schema = array_merge($schema, [
				'content'	=> $html,
				'label'		=> $html,
			]);
		}

		if ($event instanceof EventLinkable) {
			$schema = array_merge($schema, [
				'url'		=> Expect::string(),
			]);
		}

		if ($event instanceof EventRecurring) {
			$schema = array_merge($schema, [
				'daysOfWeek' => Expect::listOf($day),
				'startRecur' => (clone $date)->nullable(),
				'endRecur'	 => (clone $date)->nullable(),
				'startTime'	 => Expect::string(),
				'endTime'	 => Expect::string(),
			]);
		}

		return Expect::structure($schema)
			->skipDefaults();
	}
}
