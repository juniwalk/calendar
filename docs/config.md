# Configuration

Configure your Calendar component in `config.neon` file.

```neon
extensions:
	calendar: JuniWalk\Calendar\DI\CalendarExtension

calendar:
	# Json configuration for FullCalendar
	options:
		themeSystem: 'bootstrap5'
	# Globaly registered sources included in each Calendar instance
	sources:
		# Include if you want to show Czech holidays as background events
		- JuniWalk\Calendar\Sources\CzechHolidaysSource

# Include path to the locale file in your Translation component
# If you are using Contributte\Translation this gets registered for you
translation:
	dirs:
		- %vendorDir%/juniwalk/calendar/locale
		- %appDir%/locale
```

## Assets 

Dont forget to include assets in html

- `%vendorDir%/juniwalk/calendar/assets/calendar.bs5.css`
- `%moduleDir%/fullcalendar/index.global.min.js`


# Custom source

To show your data in the calendar, create custom class implementing `JuniWalk\Calendar\Source` interface which you then register either globally or as a service and then add it manualy.

```php
namespace App\Components\Calendar;

use DateTime;
use DateTimeZone;
use JuniWalk\Calendar\Calendar;
use JuniWalk\Calendar\Config;
use JuniWalk\Calendar\Entity\Legend;
use JuniWalk\Calendar\Event;
use JuniWalk\Calendar\EventLinkable;
use JuniWalk\Calendar\EventProvider;
use JuniWalk\Calendar\Exceptions\EventInvalidException;
use JuniWalk\Calendar\Exceptions\EventNotEditableException;
use JuniWalk\Calendar\Exceptions\EventNotFoundException;
use JuniWalk\Calendar\Source;
use JuniWalk\Calendar\SourceEditable;
use JuniWalk\Calendar\SourceLinkable;
use Nette\Application\UI\Component;
use Nette\Application\UI\Link;
use Throwable;

final class ExampleSource extends Component implements Source, SourceEditable, SourceLinkable
{
	private Config $config;
	private bool $isEditable = true;

	public function __construct(
		// TODO: Include custom dependencies
	) {
	}


	public function setConfig(Config $config): void
	{
		$this->config = $config;
	}


	public function setEditable(bool $isEditable): void
	{
		$this->isEditable = $isEditable;
	}


	/**
	 * @return Legend[]
	 */
	public function getLegend(): array
	{
		return [];
	}


	/**
	 * @throws EventInvalidException
	 * @throws EventNotEditableException
	 */
	public function eventDrop(int $id, DateTime $start, bool $allDay): void
	{
		if (!$this->isEditable || !$this->config->isEditable()) {
			throw new EventNotEditableException;
		}

		try {
			// TODO: Load your event from using given identificator
			// $event = $this->eventRepository->getById($id);
			$end = (clone $start)->add($event->getDuration());

			$event->setStart($start);
			$event->setEnd($end);

			$this->config->checkOutOfBounds($event);
			// TODO: Save changes to your event

		} catch (Throwable $e) {
			throw EventNotFoundException::fromSource($this, $id, $e);
		}
	}


	/**
	 * @throws EventInvalidException
	 * @throws EventNotEditableException
	 */
	public function eventResize(int $id, DateTime $end, bool $allDay): void
	{
		if (!$this->isEditable || !$this->config->isEditable()) {
			throw new EventNotEditableException;
		}

		try {
			// TODO: Load your event from using given identificator
			// $event = $this->eventRepository->getById($id);

			if ($allDay && $end->format('H:i') === '00:00') {
				$end = (clone $end)->modify('-1 day')->modify(
					$event->getEnd()?->format('H:i') ?? '00:00'
				);
			}

			$event->setEnd($end);

			$this->config->checkOutOfBounds($event);
			// TODO: Save changes to your event

		} catch (Throwable $e) {
			throw EventNotFoundException::fromSource($this, $id, $e);
		}
	}


	public function eventLink(Event & EventLinkable $event, Calendar $calendar): string|Link
	{
		// TODO: Allow you to make event in the calendar clickable by providing link to destination
		return $calendar->createLink(':Module:Presenter:action', ['query' => $event->id]);
	}


	/**
	 * @return Event[]|EventProvider[]
	 */
	public function fetchEvents(DateTime $start, DateTime $end, DateTimeZone $timeZone): array
	{
		// TODO: Return list of Event|EventProvider instances
		// return $this->eventRepository->getAll();
	}


	// TODO: Create custom controls in the top of the Calendar control
	public function attachControls(Calendar $calendar): void {}
	public function detachControls(Calendar $calendar): void {}
}
```


# Component factory

Create factory for your component in desired Presenter.

```php

use App\Components\Calendar\ExampleSource;
use JuniWalk\Calendar\Calendar;
use JuniWalk\Calendar\CalendarFactory;

final class ExamplePresenter
{
	public function __construct(
		private readonly CalendarFactory $calendarFactory,
	) {
	}


	public function createComponentCalendar(): Calendar
	{
		$calendar = $this->calendarFactory->create();
		$calendar->addSource(new ExampleSource);

		// TODO: Add handler for a click into empty day to create new events
		// $calendar->setClickHandle(function($self, $start) {
		// 	$this->redirect('createEvent');
		// });

		return $calendar;
	}
}
```
