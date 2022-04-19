<?php declare(strict_types=1);

namespace Forrest79\ComposerNY\Files;

final class ComposerJson extends ComposerFile
{

	protected function composerType(): string
	{
		return 'json';
	}


	public function remove(): void
	{
		@unlink($this->getComposerPath()); // intentionally @ - file may not exist
	}

}
