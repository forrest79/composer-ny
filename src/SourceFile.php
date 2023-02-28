<?php declare(strict_types=1);

namespace Forrest79\ComposerNY;

use Composer;
use Nette\Neon\Neon;
use Symfony\Component\Yaml;

final class SourceFile
{
	private const JSON = 'composer.json';
	private const NEON = 'composer.neon';
	private const YML = 'composer.yml';
	private const YAML = 'composer.yaml';

	private string $workingDir;

	private string|NULL $detectedSource = NULL;

	private string|NULL $originalJson = NULL;

	private bool $removeJson = TRUE;


	public function __construct(string $workingDir)
	{
		$this->workingDir = $workingDir;
	}


	public function prepareJson(): void
	{
		$existingSources = [];
		foreach ([self::JSON, self::NEON, self::YML, self::YAML] as $source) {
			if (is_file($this->getSourcePath($source))) {
				$existingSources[] = $source;
				$this->detectedSource = $source;
			}
		}

		if (count($existingSources) > 1) {
			$this->detectedSource = NULL;
			throw new Exceptions\TooManySourcesException($existingSources);
		}

		if ($this->isJson()) {
			$this->keepJson();
		} else if ($this->detectedSource !== NULL) {
			$path = $this->getSourcePath($this->detectedSource);
			$data = self::fileGetContent($path);
			$array = [];

			if ($this->isNeon()) {
				$array = Neon::decode($data);
			} else if ($this->isYaml()) {
				$array = Yaml\Yaml::parse($data);
			}

			assert(is_array($array));
			$this->writeJson($array);
		}
	}


	public function isJson(): bool
	{
		$this->checkPrepareJson();

		return $this->detectedSource === self::JSON;
	}


	public function isNeon(): bool
	{
		return $this->detectedSource === self::NEON;
	}


	public function isYaml(): bool
	{
		return ($this->detectedSource === self::YML) || ($this->detectedSource === self::YAML);
	}


	/**
	 * @param array<mixed> $data
	 */
	private function writeJson(array $data): void
	{
		$path = $this->getSourcePath(self::JSON);
		(new Composer\Json\JsonFile($path))->write($data);

		$data = self::fileGetContent($path);

		$this->originalJson = $data;
	}


	public function clean(): void
	{
		if ($this->detectedSource === NULL) {
			return;
		}

		$jsonPath = $this->getSourcePath(self::JSON);

		if ($this->originalJson !== NULL) {
			$newJson = self::fileGetContent($jsonPath);

			if ($this->originalJson !== $newJson) {
				$json = json_decode($newJson, TRUE);
				$error = json_last_error();
				if ($error !== JSON_ERROR_NONE) {
					throw new Exceptions\RuntimeException(json_last_error_msg(), $error);
				}

				$sourcePath = $this->getSourcePath($this->detectedSource);
				$newSource = '';
				if ($this->isNeon()) {
					$newSource = trim(Neon::encode($json, TRUE)) . PHP_EOL;
				} else if ($this->isYaml()) {
					$newSource = Yaml\Yaml::dump($json, 100);
				}

				file_put_contents($sourcePath . '.' . time(), $newSource);
			}
		}

		if ($this->removeJson) {
			@unlink($this->getSourcePath(self::JSON)); // intentionally @ - file may not exists
		}
	}


	public function getSource(): string
	{
		$this->checkPrepareJson();

		assert(is_string($this->detectedSource));

		return $this->detectedSource;
	}


	public function keepJson(): void
	{
		$this->removeJson = FALSE;
	}


	private function getSourcePath(string $file): string
	{
		return rtrim($this->workingDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
	}


	private function checkPrepareJson(): void
	{
		if ($this->detectedSource === NULL) {
			throw new Exceptions\RuntimeException('Run prepareJson() first.');
		}
	}


	private static function fileGetContent(string $path): string
	{
		$data = @file_get_contents($path); // intentionally @
		if ($data === FALSE) {
			throw new Exceptions\FileSystemException(sprintf('File \'%s\' not exists or is not readable.', $path));
		}

		return $data;
	}

}
