<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\DI;

use Contributte\Translation\Translator;
use InvalidArgumentException;
use JuniWalk\Calendar\CalendarFactory;
use JuniWalk\Calendar\Entity\Parameters;
use JuniWalk\Calendar\Source;
use JuniWalk\Utils\Format;
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
			'sources' => Expect::listOf(
				Expect::string()->dynamic()
			),
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

		$calendar = $builder->addFactoryDefinition($this->prefix('calendar'))
			->setImplement(CalendarFactory::class);

		foreach ($config->sources as $source) {
			if (is_string($source) && !str_starts_with($source, '@')) {
				if (!is_a($source, Source::class, true)) {
					throw new InvalidArgumentException('Source must implement interface "'.Source::class.'".');
				}
	
				$className = Format::camelCase(Format::className($source));
				$source = $builder->addDefinition($this->prefix('source.'.$className))
					->setFactory($source);
			}

			$calendar->getResultDefinition()->addSetup('addSource', [$source]);
		}
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
