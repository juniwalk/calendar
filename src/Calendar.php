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
use Iterator;
use JuniWalk\Calendar\Entity\Legend;
use JuniWalk\Calendar\Entity\Options;
use JuniWalk\Calendar\Enums\View;
use JuniWalk\Calendar\Exceptions\ConfigInvalidParamException;
use JuniWalk\Calendar\Exceptions\EventInvalidException;
use JuniWalk\Calendar\Exceptions\EventNotFoundException;
use JuniWalk\Calendar\Exceptions\SourceAttachedException;
use JuniWalk\Calendar\Exceptions\SourceNotEditableException;
use JuniWalk\Components\Actions\LinkProvider;
use JuniWalk\Components\Actions\Traits\Actions;
use JuniWalk\Components\Actions\Traits\Links;
use JuniWalk\Utils\Enums\Casing;
use JuniWalk\Utils\Format;
use JuniWalk\Utils\Interfaces\EventHandler;
use JuniWalk\Utils\Traits\Events;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\InvalidArgumentException;
use Nette\Http\IRequest as HttpRequest;
use Nette\Localization\Translator;
use Throwable;
use Tracy\Debugger;

class Calendar extends Control implements EventHandler, LinkProvider
{
	use Actions, Links, Events;

	private readonly Translator $translator;
	private Config $config;

	public function __construct(
		Options $options,
		HttpRequest $httpRequest,
		Translator $translator,
		?Config $config = null,
	) {
		$this->config = $config ?? $options;
		$this->translator = $translator;

		$this->monitor(Presenter::class, fn() => $this->config->loadState($this, $httpRequest));
		$this->watch('render')->watch('fetch');
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


	/**
	 * @return array<string, Source>
	 */
	public function getSources(): array
	{
		/** @var Iterator<string, Source> */
		$sources = $this->getComponents(false, Source::class);
		return iterator_to_array($sources);
	}


	/**
	 * @param null|callable(self, DateTime): void $callback
	 */
	public function setClickHandle(?callable $callback): void
	{
		$this->watch('click', true);

		if (is_callable($callback)) {
			$this->when('click', $callback);
		}
	}


	public function isClickHandled(): bool
	{
		return $this->isWatched('click');
	}


	public function handleClick(?string $start): void
	{
		if (!$start || !$this->isClickHandled()) {
			return;
		}

		$date = new DateTime($start);
		$time = $this->config->findMinTime(
			(int) $date->format('N')
		);

		if ($time && $date->format('H:i') === '00:00') {
			$date->modify($time);
		}

		$this->trigger('click', $this, $date);
	}


	public function handleDrop(?string $type, ?int $id, ?string $start, ?bool $allDay): void
	{
		$presenter = $this->getPresenter();

		try {
			if (!isset($type) || !isset($id) || !isset($start) || !isset($allDay)) {
				throw new EventNotFoundException;
			}

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

		/** @deprecated Do backwards compatibility only */
		if (method_exists($presenter, 'redirectAjax')) {
			$presenter->redirectAjax('this');
			$presenter->sendPayload();
		}

		$presenter->redirect('this');
	}


	public function handleResize(?string $type, ?int $id, ?string $end, ?bool $allDay): void
	{
		$presenter = $this->getPresenter();

		try {
			if (!isset($type) || !isset($id) || !isset($end) || !isset($allDay)) {
				throw new EventNotFoundException;
			}

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

		/** @deprecated Do backwards compatibility only */
		if (method_exists($presenter, 'redirectAjax')) {
			$presenter->redirectAjax('this');
			$presenter->sendPayload();
		}

		$presenter->redirect('this');
	}


	public function handleFetch(?string $start, ?string $end, ?string $timeZone): void
	{
		try {
			if (!$start || !$end || !$timeZone) {
				throw new EventNotFoundException;
			}

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

		/** @var DefaultTemplate */
		$template = $this->createTemplate();
		$template->setFile(__DIR__.'/templates/'.$config->getTheme()->value.'.latte');
		$template->setTranslator($this->translator);

		$this->trigger('render', $this, $template);

		$template->setParameters([
			'controlName' => $this->getName(),
			'actions' => $this->getActions(),
			'sources' => $this->getSources(),
			'legend' => Legend::class,
			'views' => View::cases(),
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
	 * @return object[]
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

				if (!$event instanceof Event) {
					throw EventInvalidException::fromValue($event);
				}

				$event->setSource($type);

				if ($event instanceof EventLinkable &&
					$source instanceof SourceLinkable) {
					$event->setUrl($source->eventLink($event, $this));
				}

				if (!$this->config->isVisible($event)) {
					$event->setAllDay(true);
				}

				$events[] = $event;
			}
		}

		if (!$this->config->isShowAllDayEvents()) {
			$events = $this->createAllDayEventsChunked($events);
		}

		foreach ($events as $key => $event) {
			$source = $sources[$event->getSource()];

			try {
				$events[$key] = $validator->validate($event, $source);

			} catch (Throwable $e) {
				Debugger::log($e);
				continue;
			}
		}

		return array_values($events);
	}


	/**
	 * @param  Event[] $events
	 * @return Event[]
	 */
	private function createAllDayEventsChunked(array $events): array
	{
		$hours = $this->config->getBusinessHours();
		$interval = new DateInterval('PT30M');
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

			/** @var DateTime $eventStart */
			$eventStart = $event->getStart();
			$eventEnd = $event->getEnd() ?? (clone $eventStart)->modify('+1 hour');
			$dateRange = new DatePeriod($eventStart, $interval, $eventEnd);
			$chunks = [];

			/** @var DateTime $date */
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

			foreach ($chunks as $chunk) {
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
}
