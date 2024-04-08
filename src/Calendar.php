<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use JuniWalk\Calendar\Entity\Legend;
use JuniWalk\Calendar\Entity\Options;
use JuniWalk\Calendar\Exceptions\ConfigInvalidParamException;
use JuniWalk\Calendar\Exceptions\EventInvalidException;
use JuniWalk\Calendar\Exceptions\SourceAttachedException;
use JuniWalk\Calendar\Exceptions\SourceNotEditableException;
use JuniWalk\Utils\Enums\Casing;
use JuniWalk\Utils\Format;
use JuniWalk\Utils\Traits\Events;
use JuniWalk\Utils\UI\Actions\LinkProvider;
use JuniWalk\Utils\UI\Actions\Traits\Actions;
use JuniWalk\Utils\UI\Actions\Traits\Links;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\InvalidArgumentException;
use Nette\Http\IRequest as HttpRequest;
use Nette\Localization\Translator;
use Throwable;
use Tracy\Debugger;

class Calendar extends Control implements LinkProvider
{
	use Actions, Links, Events;

	public function __construct(
		Options $options,
		HttpRequest $httpRequest,
		private readonly Translator $translator,
		private ?Config $config = null,
	) {
		$config = $this->config ??= $options;

		$this->watch('render');
		$this->watch('fetch');

		$this->monitor(Presenter::class, fn() => $config->loadState($this, $httpRequest));
	}


	public function getConfig(): Config
	{
		return $this->config;
	}


	/**
	 * @throws ConfigInvalidParamException
	 */
	public function setParam(string $param, mixed $value): void
	{
		$this->config->setParam($param, $value);
	}


	/**
	 * @throws ConfigInvalidParamException
	 */
	public function getParam(string $param, bool $throw = true): mixed
	{
		return $this->config->getParam($param, $throw);
	}


	/**
	 * @throws SourceAttachedException
	 */
	public function addSource(Source $source, ?string $name = null): void
	{
		$name ??= Format::className($source, Casing::Camel, 'Source');

		if ($parent = $source->getParent()) {
			throw SourceAttachedException::fromName($name, $parent);
		}

		$source->setConfig($this->config);
		$source->monitor(Presenter::class, function() use ($source) {
			$source->monitor($this::class, attached: fn() => $source->attachControls($this));
			$source->monitor($this::class, detached: fn() => $source->detachControls($this));
		});

		$this->addComponent($source, $name);
	}


	/**
	 * @throws InvalidArgumentException
	 */
	public function getSource(string $name): Source
	{
		return $this->getComponent($name);
	}


	public function getSources(): iterable
	{
		return $this->getComponents(false, Source::class);
	}


	public function setClickHandle(?callable $callback): void
	{
		$this->watch('click', true)->when('click', $callback);
	}


	public function isClickHandled(): bool
	{
		return $this->isWatched('click');
	}


	public function handleClick(?string $start): void
	{
		$start = new DateTime($start);
		$dow = (int) $start->format('N');

		if (!$this->isClickHandled()) {
			return;
		}

		$time = $this->config->findMinTime($dow);

		if ($time && $start->format('H:i') === '00:00') {
			$start->modify($time);
		}

		$this->trigger('click', $this, $start);
	}


	public function handleDrop(?string $type, ?int $id, ?string $start, ?bool $allDay): void
	{
		$presenter = $this->getPresenter();

		try {
			$source = $this->getSource($type);

			if (!$source instanceof SourceEditable) {
				throw SourceNotEditableException::fromSource($source);
			}

			$source->eventDrop($id, new DateTime($start), $allDay);

		} catch (EventInvalidException $e) {
			$presenter->flashMessage('web.message.'.Format::className($e), 'warning');

		} catch (Throwable $e) {
			$presenter->flashMessage('web.message.something-went-wrong', 'danger');
			Debugger::log($e);
		}

		$presenter->redirectAjax('this');
		$presenter->sendPayload();
	}


	public function handleResize(?string $type, ?int $id, ?string $end, ?bool $allDay): void
	{
		$presenter = $this->getPresenter();

		try {
			$source = $this->getSource($type);

			if (!$source instanceof SourceEditable) {
				throw SourceNotEditableException::fromSource($source);
			}

			$source->eventResize($id, new DateTime($end), $allDay);

		} catch (EventInvalidException $e) {
			$presenter->flashMessage('web.message.'.Format::className($e), 'warning');

		} catch (Throwable $e) {
			$presenter->flashMessage('web.message.something-went-wrong', 'danger');
			Debugger::log($e);
		}

		$presenter->redirectAjax('this');
		$presenter->sendPayload();
	}


	public function handleFetch(?string $start, ?string $end, ?string $timeZone): void
	{
		try {
			$events = $this->fetchEvents(
				new DateTime($start),
				new DateTime($end),
				new DateTimeZone($timeZone),
			);

		} catch (Throwable $e) {
			Debugger::log($e);
			$events = [];
		}

		$this->getPresenter()->sendJson($events);
	}


	public function render(): void
	{
		$config = $this->getConfig();

		$template = $this->createTemplate();
		$template->setFile(__DIR__.'/templates/'.$config->getTheme()->value.'.latte');
		$template->setTranslator($this->translator);

		$this->trigger('render', $this, $template);

		$template->setParameters([
			'controlName' => $this->getName(),
			'actions' => $this->getActions(),
			'sources' => $this->getSources(),
			'legend' => Legend::class,
			'config' => $config,

			'options' => [
				'data-responsive' => $config->isResponsive(),
				'data-refresh' => $config->isAutoRefresh(),
				'data-details' => $config->isShowDetails(),
			],
		]);

		$template->render();
	}


	/**
	 * @throws EventInvalidException
	 */
	private function fetchEvents(DateTime $start, DateTime $end, DateTimeZone $timeZone): array
	{
		$validator = new EventValidator;
		$sources = $this->getSources();
		$events = [];

		foreach ($sources as $type => $source) {
			$this->trigger('fetch', $this, $source);

			foreach ($source->fetchEvents($start, $end, $timeZone) as $event) {
				if ($event instanceof EventProvider) {
					$event = $event->createEvent($this->translator);
				}

				$event->setSource($type);

				if (!$event instanceof Event) {
					throw EventInvalidException::fromValue($event);
				}

				if ($event instanceof EventLinkable &&
					$source instanceof SourceLinkable) {
					$event->setUrl($source->eventLink($event, $this));
				}

				try {
					$this->config->checkOutOfBounds($event, true);
	
				} catch (EventInvalidException) {
					$event->setAllDay(true);
				}

				$events[] = $event;
			}
		}

		if (!$this->config->isShowAllDayEvents()) {
			$events = $this->createAllDayEventsChunked($events);
		}

		foreach ($events as $key => $event) {
			$source = $sources[$event->source];

			try {
				unset($events[$key]);

				$event = $validator->validate($event, $source);
				$events[$type.$event->id] = $event;

			} catch (Throwable $e) {
				Debugger::log($e);
				continue;
			}
		}

		return array_values($events);
	}


	private function createAllDayEventsChunked(array $events): array
	{
		$hours = $this->config->getBusinessHours();
		$interval = new DateInterval('PT30M');
		$result = [];

		foreach ($events as $event) {
			if (!$event->isAllday()) {
				$result[] = $event;
				continue;
			}

			$eventStart = $event->getStart();

			if (!$eventEnd = $event->getEnd()) {
				$eventEnd = (clone $eventStart)->modify('+1 hour');
			}

			$dateRange = new DatePeriod($eventStart, $interval, $eventEnd);
			$chunks = [];

			foreach ($dateRange as $date) {
				$dow = (int) $date->format('N');
				$dateStart = $hours[$dow]['start'] ?? null;
				$dateEnd = $hours[$dow]['end'] ?? null;

				if (!$dateStart || !$dateEnd) {
					continue;
				}

				$dateStart = (clone $date)->modify($dateStart);
				$dateEnd = (clone $date)->modify($dateEnd);

				if (!isset($chunks[$dow]['start']) && ($date >= $dateStart || $date == $eventStart)) {
					$chunks[$dow]['start'] = clone $date;
				}

				if (!isset($chunks[$dow]['end']) && ($date >= $dateEnd || $date == $eventEnd)) {
					$chunks[$dow]['end'] = clone $date;
				}

				if ($date == $eventEnd) {
					break;
				}
			}

			foreach ($chunks as $num => $chunk) {
				$item = clone $event;
				$item->setStart($chunk['start'] ?? $eventStart);
				$item->setEnd($chunk['end'] ?? $eventEnd);
				$item->setAllDay(false);

				// TODO: Add setGroupId to Event interface
				// $item->groupId = $event->getId();
				$item->groupId = $event->id;

				// TODO: Add chunk number to id so it does not get deduplicated
				$item->id .= '-'.$num;

				$result[] = $item;
			}
		}

		return $result;
	}
}
