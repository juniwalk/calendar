<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\DI;

use JuniWalk\Calendar\CalendarFactory;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class CalendarExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
		]);
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		// TODO: new Parameters implements Config
		// TODO: Add values from $config
		// TODO: Send to instance of Calendar

		$builder->addFactoryDefinition($this->prefix('calendar'))
			->setImplement(CalendarFactory::class);
	}
}
