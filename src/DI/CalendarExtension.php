<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2024
 * @license   MIT License
 */

namespace JuniWalk\Calendar\DI;

use Contributte\Translation\DI\TranslationProviderInterface as TranslationProvider;
use Contributte\Translation\Translator;
use JuniWalk\Calendar\CalendarFactory;
use JuniWalk\Calendar\Entity\Options;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Throwable;

final class CalendarExtension extends CompilerExtension implements TranslationProvider
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'options' => Options::createSchema(),
			'sources' => Expect::listOf(
				Expect::string()->dynamic()->transform(fn($stmt) => match (true) {
					$stmt instanceof Statement => $stmt,
					default => new Statement($stmt),
				})
			),
		]);
	}


	public function getTranslationResources(): array
	{
		return [__DIR__.'/../../locale'];
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$builder->addDefinition($this->prefix('config'))
			->addSetup('setParams', [$config->options->jsonSerialize()])
			->setClass(Options::class);

		$calendar = $builder->addFactoryDefinition($this->prefix('calendar'))
			->setImplement(CalendarFactory::class);

		foreach ($config->sources as $source) {
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
			// Contributte/Translation not registered, ignore
		}
	}
}
