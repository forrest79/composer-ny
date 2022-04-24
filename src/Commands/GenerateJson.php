<?php declare(strict_types=1);

namespace Forrest79\ComposerNY\Commands;

use Forrest79\ComposerNY;
use Symfony\Component\Console\Command;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;

/**
 * @method ComposerNY\Console\Application getApplication()
 */
final class GenerateJson extends Command\Command
{

	public function __construct()
	{
		parent::__construct('generate-json');
	}


	protected function configure(): void
	{
		$this->setDescription('Generate global composer.json from composer.neon/composer.yaml.');
	}


	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		if ($this->getApplication()->composerIsJsonSource()) {
			$output->writeln('<comment>There is already original composer.json, no other was generated.</comment>');
		} else {
			$this->getApplication()->composerKeepJson();

			$output->writeln(sprintf('<info>composer.json was generated from %s.</info>', $this->getApplication()->composerGetSourceFile()));
		}

		return self::SUCCESS;
	}

}
