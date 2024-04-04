<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTime;
use DateTimeZone;
use JuniWalk\Calendar\Entity\Legend;
use JuniWalk\Calendar\Entity\Parameters;
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
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nette\Utils\Html;
use Throwable;
use Tracy\Debugger;

class Calendar extends Control implements LinkProvider
{
	use Actions, Links, Events;

	public function __construct(
		private readonly Parameters $parameters,
		private readonly Translator $translator,
		private HttpRequest $httpRequest,
		private ?Config $config = null,
	) {
		$config = $this->config ??= $parameters;

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
		return $this->isWatched(('click'));
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


	// TODO: Move to SourceManager and link as source-drop!
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


	// TODO: Move to SourceManager and link as source-resize!
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


	// TODO: Move to SourceManager and link as source-fetch!
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
		$template = $this->createTemplate();
		$template->setFile(__DIR__.'/templates/default.latte');
		$template->setTranslator($this->translator);

		$this->trigger('render', $this, $template);

		$template->setParameters([
			'config' => $config = $this->config,
			'controlName' => $this->getName(),
			'actions' => $this->getActions(),
			'sources' => $this->getSources(),
			'legend' => Legend::class,

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
		$events = [];

		// TODO: Create SourceManager that would service all sources
		foreach ($this->getSources() as $type => $source) {
			$this->trigger('fetch', $this, $source);

			// TODO: Create method createEvent in SourceManager and handle all of this cycle inside
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

				if (!$this->config->isVisible($event)) {
					$event->setAllDay(true);
				}

				try {
					$params = (new Processor)->process(
						$this->eventSchema($event, $type),
						$event->jsonSerialize(),
					);

					$events[$type.$params->id] = $params;

				} catch (Throwable $e) {
					Debugger::log($e);
					continue;
				}
			}
		}

		return array_values($events);
	}


	// TODO: Move into EventValidator or SourceManager class
	private function eventSchema(Event $event, string $source): Schema
	{
		// TODO: Cache schema using $event::class as it will be the same for all instances

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
			'source'		=> Expect::string()->required()->assert(fn($s) => $s === $source, 'Event source must be name of the source'),
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
