<?php declare(strict_types=1);

namespace Forrest79\ComposerNY\Files;

use Nette\Neon\Neon;

final class ComposerNeon extends ComposerJsonWriter
{

	protected function composerType(): string
	{
		return 'neon';
	}


	/**
	 * @return array<string, mixed>
	 */
	protected function getData(): array
	{
		if (!$this->exists()) {
			throw new \Forrest79\ComposerNY\Exceptions\FileSystemException('todo');
		}

		$data = file_get_contents($this->getComposerPath());
		if ($data === FALSE) {
			throw new \Forrest79\ComposerNY\Exceptions\FileSystemException('todo');
		}

		$neon = Neon::decode($data);
		assert(is_array($neon));

		return $neon;
	}

}