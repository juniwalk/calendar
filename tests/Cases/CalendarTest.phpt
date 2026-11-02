<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2026
 * @license   MIT License
 */

namespace JuniWalk\Tests\Cases;

require __DIR__.'/../bootstrap.php';

use JuniWalk\Calendar\Calendar;
use JuniWalk\Calendar\Capabilities;
use JuniWalk\Calendar\Config;
use JuniWalk\Calendar\Enums\Capability;
use Nette\Application\ForbiddenRequestException;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\Localization\Translator;
use Tester\Assert;
use Tester\TestCase;

/**
 * @testCase
 */
final class CalendarTest extends TestCase
{
	public function testClickDenied(): void
	{
		$calendar = $this->createCalendar(Capability::CalendarClick);
		$calendar->setClickHandle(fn() => Assert::fail('Click handler must not be called.'));

		Assert::exception(
			fn() => $calendar->handleClick('2025-01-01'),
			ForbiddenRequestException::class,
		);
	}


	public function testDropDenied(): void
	{
		$calendar = $this->createCalendar(Capability::EventDrop);

		Assert::exception(
			fn() => $calendar->handleDrop('test', 1, '2025-01-01', false),
			ForbiddenRequestException::class,
		);
	}


	public function testResizeDenied(): void
	{
		$calendar = $this->createCalendar(Capability::EventResize);

		Assert::exception(
			fn() => $calendar->handleResize('test', 1, '2025-01-01', false),
			ForbiddenRequestException::class,
		);
	}


	public function testClickAllowed(): void
	{
		$calendar = $this->createCalendar();
		$clicked = null;

		$calendar->setClickHandle(function($calendar, $date) use (&$clicked) {
			$clicked = $date->format('Y-m-d');
		});

		$calendar->handleClick('2025-01-01');
		Assert::same('2025-01-01', $clicked);
	}


	private function createCalendar(Capability ...$denied): Calendar
	{
		$translator = new class implements Translator {
			public function translate(string|\Stringable $message, mixed ...$parameters): string
			{
				return (string) $message;
			}
		};

		return new Calendar(
			new Request(new UrlScript('http://localhost')),
			new Config,
			$translator,
			new Capabilities(...$denied),
		);
	}
}

(new CalendarTest)->run();
