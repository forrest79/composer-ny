<?php declare(strict_types=1);

namespace Forrest79\ComposerNY\Console;

use Composer;
use Forrest79\ComposerNY;
use Symfony\Component\Console;

final class Application extends Composer\Console\Application
{
	private const VERSION = '1.0.0';

	private ComposerNY\SourceFile $composerSourceFile;

	/**
	 * When running multiple Commands on one Application instance do logic with composer.neon/composer.yaml only on the first Command.
	 */
	private bool $firstDoRun = TRUE;


	public function doRun(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
	{
		if (!$this->firstDoRun) {
			return parent::doRun($input, $output);
		}

		$this->firstDoRun = FALSE;
		$this->composerSourceFile = new ComposerNY\SourceFile($this->getWorkingDirectory($input));

		$exitCode = Console\Command\Command::FAILURE;

		try {
			$this->composerSourceFile->prepareJson();

			$exitCode = parent::doRun($input, $output);

			if (!$this->composerSourceFile->isJson()) {
				$output->writeln(PHP_EOL . sprintf('<comment>[Data from %s was used]</comment>', $this->composerSourceFile->getSource()));
			}
		} catch (ComposerNY\Exceptions\TooManySourcesException $e) {
			$output->writeln(sprintf(
				'<error>Files %s are presented in working directory - use just one of them.</error>',
				implode(' and ', $e->getExistingSources()),
			));
		} finally {
			$this->composerSourceFile->clean();
		}

		return $exitCode;
	}


	public function composerKeepJson(): void
	{
		$this->composerSourceFile->keepJson();
	}


	public function composerGetSourceFile(): string
	{
		return $this->composerSourceFile->getSource();
	}


	public function composerIsJsonSource(): bool
	{
		return $this->composerSourceFile->isJson();
	}


	/**
	 * @return array<Console\Command\Command>
	 */
	protected function getDefaultCommands(): array
	{
		$existingDefaultCommands = parent::getDefaultCommands();

		// remove original SelfUpdateCommand - there is no self-update available for ComposerNY yet
		$existingDefaultCommands = array_filter($existingDefaultCommands, static fn ($command) => !($command instanceof Composer\Command\SelfUpdateCommand));

		return array_merge($existingDefaultCommands, [
			new ComposerNY\Commands\GenerateJson(),
		]);
	}


	public function getLongVersion(): string
	{
		return parent::getLongVersion() . sprintf(' {(N)eon (Y)aml <info>%s</info>}', self::VERSION);
	}


	private function getWorkingDirectory(Console\Input\InputInterface $input): string
	{
		$workingDir = $input->getParameterOption(['--working-dir', '-d']);
		assert($workingDir === FALSE || is_string($workingDir));

		if ($workingDir !== FALSE && !is_dir($workingDir)) {
			throw new ComposerNY\Exceptions\RuntimeException(sprintf('Invalid working directory specified, %s does not exist.', $workingDir));
		}

		return $workingDir === FALSE
			? ($this->getInitialWorkingDirectory() === FALSE ? throw new ComposerNY\Exceptions\RuntimeException('Can\'t get initial working directory') : $this->getInitialWorkingDirectory())
			: $workingDir;
	}

}
