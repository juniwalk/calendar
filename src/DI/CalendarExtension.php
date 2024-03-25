<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\DI;

use Contributte\Translation\Translator;
use JuniWalk\Calendar\CalendarFactory;
use JuniWalk\Calendar\Entity\Parameters;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Throwable;

final class CalendarExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'config' => Parameters::createSchema(),
		]);
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$builder->addDefinition($this->prefix('config'))
			->addSetup('setParams', [$config->config->jsonSerialize()])
			->setClass(Parameters::class);

		$builder->addFactoryDefinition($this->prefix('calendar'))
			->setImplement(CalendarFactory::class);
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		try {
			$translator = $builder->getByType(Translator::class, true);
			$locale = new Statement(['@'.$translator, 'getLocale']);

			$builder->getDefinition($this->prefix('config'))
				->addSetup('setLocale', [$locale]);

		} catch (Throwable) {
		}
	}
}
