<?php declare(strict_types=1);

namespace Forrest79\ComposerNY\Files;

abstract class ComposerFile
{
	private string $composerPath;


	public function __construct(string $currentDir)
	{
		$this->composerPath = rtrim($currentDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.' . $this->composerType();
	}


	abstract protected function composerType(): string;


	final protected function getComposerPath(): string
	{
		return $this->composerPath;
	}


	final public function getComposerFile(): string
	{
		return basename($this->composerPath);
	}


	final public function exists(): bool
	{
		return is_file($this->composerPath);
	}

}
