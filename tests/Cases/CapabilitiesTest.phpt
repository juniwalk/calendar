<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2026
 * @license   MIT License
 */

namespace JuniWalk\Tests\Cases;

require __DIR__.'/../bootstrap.php';

use DateTimeImmutable;
use JuniWalk\Calendar\Capabilities;
use JuniWalk\Calendar\Entity\Activity;
use JuniWalk\Calendar\Enums\Capability;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

/**
 * @testCase
 */
final class CapabilitiesTest extends TestCase
{
	public function testAllAllowedByDefault(): void
	{
		$capabilities = new Capabilities;

		foreach (Capability::cases() as $capability) {
			Assert::true($capabilities->isAllowed($capability));
		}
	}


	public function testConstructorDeniesGiven(): void
	{
		$capabilities = new Capabilities(Capability::EventDrop, Capability::EventResize);

		Assert::false($capabilities->isAllowed(Capability::EventDrop));
		Assert::false($capabilities->isAllowed(Capability::EventResize));
		Assert::true($capabilities->isAllowed(Capability::EventLink));
	}


	public function testAllowDeny(): void
	{
		$capabilities = new Capabilities;

		$capabilities->deny(Capability::CalendarClick);
		Assert::false($capabilities->isAllowed(Capability::CalendarClick));

		$capabilities->allow(Capability::CalendarClick);
		Assert::true($capabilities->isAllowed(Capability::CalendarClick));
	}


	public function testSetAllowed(): void
	{
		$capabilities = new Capabilities;

		$capabilities->setAllowed(Capability::EventLink, false);
		Assert::false($capabilities->isAllowed(Capability::EventLink));

		$capabilities->setAllowed(Capability::EventLink, true);
		Assert::true($capabilities->isAllowed(Capability::EventLink));
	}


	public function testApplyKeepsEverythingWhenAllowed(): void
	{
		$event = $this->createEvent();
		(new Capabilities)->apply($event);

		Assert::same('/detail', $event->url);
		Assert::type(Html::class, $event->content);
		Assert::type(Html::class, $event->label);
	}


	public function testApplyStripsDetail(): void
	{
		$event = $this->createEvent();
		(new Capabilities(Capability::EventDetail))->apply($event);

		Assert::null($event->content);
		Assert::null($event->label);
		Assert::same('/detail', $event->url);
		Assert::false(isset($event->jsonSerialize()['content']));
		Assert::false(isset($event->jsonSerialize()['label']));
	}


	public function testApplyStripsLink(): void
	{
		$event = $this->createEvent();
		(new Capabilities(Capability::EventLink))->apply($event);

		Assert::null($event->url);
		Assert::type(Html::class, $event->content);
		Assert::false(isset($event->jsonSerialize()['url']));
	}


	private function createEvent(): Activity
	{
		return new Activity(null, [
			'title' => 'Event',
			'source' => 'test',
			'start' => new DateTimeImmutable('2025-01-01 10:00'),
			'url' => '/detail',
			'content' => Html::el('p', 'Content'),
			'label' => Html::el('b', 'Label'),
		]);
	}
}

(new CapabilitiesTest)->run();
