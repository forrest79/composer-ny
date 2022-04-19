<?php declare(strict_types=1);

namespace Forrest79\ComposerNY\Commands;

use Forrest79\ComposerNY;
use Symfony\Component\Console\Command;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;

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
		throw new ComposerNY\Exceptions\KeepComposerJsonException();
	}

}
