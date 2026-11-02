<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2026
 * @license   MIT License
 */

namespace JuniWalk\Calendar;

use JuniWalk\Calendar\Enums\Capability;

class Capabilities
{
	/**
	 * ? List of capabilities the current user is denied to use
	 * @var array<value-of<Capability>, true>
	 */
	protected array $denied = [];


	public function __construct(Capability ...$denied)
	{
		foreach ($denied as $capability) {
			$this->deny($capability);
		}
	}


	/**
	 * @param value-of<Capability> $capability
	 */
	public function allow(Capability|string $capability): void
	{
		if (is_string($capability)) {
			$capability = Capability::from($capability);
		}

		unset($this->denied[$capability->value]);
	}


	/**
	 * @param value-of<Capability> $capability
	 */
	public function deny(Capability|string $capability): void
	{
		if (is_string($capability)) {
			$capability = Capability::from($capability);
		}

		$this->denied[$capability->value] = true;
	}


	/**
	 * @param value-of<Capability> $capability
	 */
	public function setAllowed(Capability|string $capability, bool $allowed): void
	{
		$allowed ? $this->allow($capability) : $this->deny($capability);
	}


	/**
	 * @param value-of<Capability> $capability
	 */
	public function isAllowed(Capability|string $capability): bool
	{
		if (is_string($capability)) {
			$capability = Capability::from($capability);
		}

		return !isset($this->denied[$capability->value]);
	}


	/**
	 * @return array<string, bool>
	 */
	public function toArray(): array
	{
		$list = [];

		foreach (Capability::cases() as $capability) {
			$list[$capability->value] = $this->isAllowed($capability);
		}

		return $list;
	}


	public function apply(Event $event): void
	{
		// ? Remove detail label and content if the capability is denied
		if ($event instanceof EventDetail && !$this->isAllowed(Capability::EventDetail)) {
			$event->content = null;
			$event->label = null;
		}

		// ? Remove event link if the capability is denied
		if ($event instanceof EventLinkable && !$this->isAllowed(Capability::EventLink)) {
			$event->url = null;
		}
	}
}
