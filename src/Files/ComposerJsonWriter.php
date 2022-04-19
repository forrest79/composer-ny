<?php declare(strict_types=1);

namespace Forrest79\ComposerNY\Files;

use Composer;

abstract class ComposerJsonWriter extends ComposerFile
{

	/**
	 * @return array<string, mixed>
	 */
	abstract protected function getData(): array;


	final public function saveJson(): void
	{
		$file = new Composer\Json\JsonFile($this->getComposerPath());
		$file->write($this->getData());
	}

}
