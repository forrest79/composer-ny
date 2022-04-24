<?php declare(strict_types=1);

namespace Forrest79\ComposerNY\Exceptions;

final class TooManySourcesException extends Exception
{
	/** @var array<string> */
	private array $existingSources = [];


	/**
	 * @param array<string> $existingSources
	 */
	public function __construct(array $existingSources)
	{
		parent::__construct();
		$this->existingSources = $existingSources;
	}


	/**
	 * @return array<string>
	 */
	public function getExistingSources(): array
	{
		return $this->existingSources;
	}

}
