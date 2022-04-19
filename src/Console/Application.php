<?php declare(strict_types=1);

namespace Forrest79\ComposerNY\Console;

use Composer;
use Forrest79\ComposerNY;
use Symfony\Component\Console;

final class Application extends Composer\Console\Application
{
	private const VERSION = '1.0.0';

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

		$workingDir = $this->getWorkingDirectory($input);

		$composerJsonFile = new ComposerNY\Files\ComposerJson($workingDir);

		/** @var array<ComposerNY\Files\ComposerFile> $composerFiles */
		$composerFiles = [
			'json' => $composerJsonFile,
			'neon' => new ComposerNY\Files\ComposerNeon($workingDir),
			'yaml' => new ComposerNY\Files\ComposerYaml($workingDir),
		];

		$existingFiles = [];

		foreach ($composerFiles as $type => $file) {
			if ($file->exists()) {
				$existingFiles[] = $file->getComposerFile();
			} else {
				unset($composerFiles[$type]);
			}
		}

		$composerFile = NULL;
		$exitCode = Console\Command\Command::FAILURE;

		if (count($composerFiles) > 1) {
			$output->writeln(sprintf(
				'<error>Files %s are presented in working directory - use just one of them.</error>',
				implode(' and ', $existingFiles),
			));
			return $exitCode;
		} else if (count($composerFiles) === 1) {
			$composerFile = reset($composerFiles);
		}

		$isComposerJson = $composerFile === $composerJsonFile;

		if (!$isComposerJson) {
			$composerFile->saveJson();
		}

		try {
			$exitCode = parent::doRun($input, $output);
			if (!$isComposerJson) {
				$output->writeln(PHP_EOL . sprintf('<comment>composer.json generated from %s was used.</comment>', $composerFile->getComposerFile()));
			}
		} catch (ComposerNY\Exceptions\KeepComposerJsonException) {
			$composerJsonFile = NULL;
			$exitCode = Console\Command\Command::SUCCESS;
		} finally {
			$composerJsonFile?->remove();
		}

		return $exitCode;
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
		return parent::getLongVersion() . sprintf(' (N)eon (Y)aml <info>%s</info>)', self::VERSION);
	}


	private function getWorkingDirectory(Console\Input\InputInterface $input): string
	{
		$workingDir = $input->getParameterOption(['--working-dir', '-d']);
		if ($workingDir !== FALSE && !is_dir($workingDir)) {
			throw new \RuntimeException(sprintf('Invalid working directory specified, %s does not exist.', $workingDir));
		}

		return $workingDir === FALSE ? $this->getInitialWorkingDirectory() : $workingDir;
	}

}
