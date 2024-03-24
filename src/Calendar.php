<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2022
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use DateTime;
use JuniWalk\Calendar\Entity\Legend;
use JuniWalk\Calendar\Exceptions\EventInvalidException;
use JuniWalk\Calendar\Exceptions\SourceNotFoundException;
use JuniWalk\Calendar\Exceptions\SourceTypeHandledException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Template;
use Nette\Http\IRequest as HttpRequest;
use Nette\Localization\Translator;

/**
 * @method void onCreate(self $self, string $view, DateTime $start)
 * @method void onRender(self $self, Template $template)
 */
class Calendar extends Control
{
	/** @var Source[] */
	private array $sources = [];

	/** @var callable[] */
	public array $onCreate = [];
	public array $onRender = [];

	public function __construct(
		private readonly Config $config,
		private readonly Translator $translator,
		private readonly HttpRequest $httpRequest,
	) {
	}


	/**
	 * @throws SourceTypeHandledException
	 */
	public function addSource(Source $source): void
	{
		$source->setConfig($this->config);

		foreach ($source->getHandlers() as $handler) {
			if ($this->sources[$handler->value] ?? null) {
				throw SourceTypeHandledException::fromHandler($handler->value);
			}

			$this->sources[$handler->value] = $source;
		}
	}


	/**
	 * @throws SourceNotFoundException
	 */
	public function getSource(string $type): Source
	{
		if (!isset($this->sources[$type])) {
			throw SourceNotFoundException::fromType($type);
		}

		return $this->sources[$type];
	}


	public function handleClick(?string $start): void
	{
		$start = $this->httpRequest->getQuery('start');

		// TODO if there is no time, use time from bussiness hours

		$this->onCreate($this, new DateTime($start));
	}


	public function handleFetch(?string $start, ?string $end): void
	{
		$start = $this->httpRequest->getQuery('start');
		$end = $this->httpRequest->getQuery('end');

		$events = $this->fetchEvents(
			new DateTime($start),
			new DateTime($end),
		);

		$this->getPresenter()->sendJson($events);
	}


	public function render(): void
	{
		$template = $this->createTemplate();
		$template->setFile(__DIR__.'/templates/default.latte');
		$template->setTranslator($this->translator);

		$this->onRender($this, $template);

		$template->setParameters([
			'locale' => $this->translator?->getLocale(),	// ? take from config
			// 'refresh' => $this->isAutoRefresh,			// ? take from config
			// 'responsive' => $this->isResponsive,			// ? take from config
			// 'editable' => $this->isEditable,				// ? take from config
			// 'popover' => $this->hasPopover,				// ? take from config
			// 'reload' => $this->isReload,					// ? take from config
			'sources' => $this->sources,
			'config' => $this->config,						// ! was named branch
			'legend' => Legend::class,

			// 'initialView' => $this->initialView,			// ? take from config
			// 'initialDate' => $this->initialDate,			// ? take from config
		]);

		$template->render();
	}


	/**
	 * @throws EventInvalidException
	 */
	private function fetchEvents(DateTime $start, DateTime $end): array
	{
		$sources = $events = [];

		foreach ($this->sources as $source) {
			if (in_array($source, $sources)) {
				continue;
			}

			// TODO Call some event on the source

			foreach ($source->createEvents($start, $end) as $event) {
				if ($event instanceof EventProvider) {
					// $event = $event->createEvent($source, $this->config);
					$event = $event->createEvent($this->translator);
				}

				if (!$event instanceof Event) {
					throw EventInvalidException::fromValue($event);
				}

				if (!$this->config->isVisible($event)) {
					$event->allDay = true;
				}

				$events[$event->getUniqueId()] = $event;
			}

			$sources[] = $source;
		}

		return array_values($events);
	}
}
