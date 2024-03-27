<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use Closure;
use DateTime;
use JuniWalk\Calendar\Entity\Legend;
use JuniWalk\Calendar\Entity\Parameters;
use JuniWalk\Calendar\Exceptions\ConfigInvalidParamException;
use JuniWalk\Calendar\Exceptions\EventInvalidException;
use JuniWalk\Calendar\Exceptions\SourceAttachedException;
use JuniWalk\Calendar\Exceptions\SourceHandledException;
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

	private array $handlers = [];

	public function __construct(
		private readonly HttpRequest $httpRequest,
		private readonly Parameters $parameters,
		private readonly Translator $translator,
		private ?Config $config = null,
	) {
		$this->config ??= $parameters;

		$this->watch('render');
	}


	public function setConfig(Config $config): void
	{
		$this->config = $config;
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
	public function getParam(string $param): mixed
	{
		return $this->config->getParam($param);
	}


	/**
	 * @throws SourceAttachedException
	 * @throws SourceHandledException
	 */
	public function addSource(Source $source, ?string $name = null): void
	{
		$name ??= Format::className($source, Casing::Camel, 'Source');
		$handlers = [];

		if ($parent = $source->getParent()) {
			throw SourceAttachedException::fromName($name, $parent);
		}

		foreach ($source->getHandlers() as $handler) {
			$handler = Format::scalarize($handler);

			if ($clash = $this->findSourceByHandler($handler)) {
				throw SourceHandledException::fromHandler($handler, $clash);
			}

			$handlers[$handler] = $name;
		}

		$source->setConfig($this->config);
		$source->monitor(Presenter::class, function() use ($source) {
			$source->monitor($this::class, attached: fn() => $source->attachControls($this));
			$source->monitor($this::class, detached: fn() => $source->detachControls($this));
		});

		$this->addComponent($source, $name);
		$this->handlers += $handlers;
	}


	public function findSourceByHandler(mixed $handler): ?Source
	{
		$handler = Format::scalarize($handler);
		$name = $this->handlers[$handler] ?? '';

		return $this->getComponent($name, false);
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


	public function setClickHandle(?Closure $callback): void
	{
		$this->watch('click', true)->when('click', $callback);
	}


	public function isClickHandled(): bool
	{
		return $this->isWatched(('click'));
	}


	public function handleClick(?string $start): void
	{
		$start = $this->httpRequest->getQuery('start');

		if (!$this->isClickHandled()) {
			return;
		}

		// TODO if there is no time, use time from bussiness hours
		$this->trigger('click', $this, new DateTime($start));
	}


	public function handleFetch(?string $start, ?string $end): void
	{
		$start = $this->httpRequest->getQuery('start');
		$end = $this->httpRequest->getQuery('end');

		try {
			$events = $this->fetchEvents(
				new DateTime($start),
				new DateTime($end),
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
			'actions' => $this->getActions(),
			'sources' => $this->getSources(),
			'config' => $this->config,
			'legend' => Legend::class,

			// 'initialView' => $this->initialView,	// ? take from config
			// 'initialDate' => $this->initialDate,	// ? take from config
			// 'refresh' => $this->isAutoRefresh,	// ? take from config
			// 'responsive' => $this->isResponsive,	// ? take from config
			// 'editable' => $this->isEditable,		// ? take from config
			// 'popover' => $this->hasPopover,		// ? take from config
			// 'reload' => $this->isReload,			// ? take from config
		]);

		$template->render();
	}


	/**
	 * @throws EventInvalidException
	 */
	private function fetchEvents(DateTime $start, DateTime $end): array
	{
		$sources = $events = [];

		foreach ($this->getSources() as $source) {
			if (in_array($source, $sources)) {
				continue;
			}

			// TODO Call some event on the source

			foreach ($source->fetchEvents($start, $end) as $event) {
				if ($event instanceof EventProvider) {
					$event = $event->createEvent($this->translator);
				}

				if (!$event instanceof Event) {
					throw EventInvalidException::fromValue($event);
				}

				if (!$this->config->isVisible($event)) {
					$event->setAllDay(true);
				}

				// TODO: Check that event type is handled and is handled by this source

				$events[$event->getUniqueId()] = $event;
			}

			$sources[] = $source;
		}

		return array_values($events);
	}
}
