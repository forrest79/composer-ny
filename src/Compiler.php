<?php declare(strict_types=1);

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Forrest79\ComposerNY;

use Composer\CaBundle\CaBundle;
use Composer\Pcre\Preg;
use Seld\PharUtils\Linter;
use Seld\PharUtils\Timestamps;
use Symfony\Component\Finder\Finder;

/**
 * The Compiler class compiles composer into a phar
 *
 * Based on original Compiler.php.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
final class Compiler
{
	private const ORIGINAL_COMPILER_MD5_HASH = '8d26b8b7f0d1afbbe1f18e40fe87e2f8';


	public function compile(string $pharFile = 'composer.phar'): void
	{
		$originalCompilerMd5Hash = md5_file(__DIR__ . '/../vendor/composer/composer/src/Composer/Compiler.php');
		if ($originalCompilerMd5Hash === FALSE) {
			throw new \RuntimeException('Can\'t load original Compiler class MD5 hash.');
		}

		if ($originalCompilerMd5Hash !== self::ORIGINAL_COMPILER_MD5_HASH) {
			throw new \RuntimeException(sprintf('Compiler.php has new MD5 hash \'%s\', expected was \'%s\'. Check for changes in original file and if needed, promote it to this file. Don\'t forget to update ORIGINAL_COMPILER_MD5_HASH constant with a new hash.', $originalCompilerMd5Hash, self::ORIGINAL_COMPILER_MD5_HASH));
		}

		$replace = [
			'require __DIR__.\'/../src/bootstrap.php\';' => 'require __DIR__.\'/../vendor/composer/composer/src/bootstrap.php\'; // @ComposerNY update',
		];

		$binComposer = file_get_contents(__DIR__ . '/../vendor/composer/composer/bin/composer');
		if ($binComposer === FALSE) {
			throw new \RuntimeException('Can\'t load original composer binary.');
		}

		$binComposer = strtr($binComposer, array_merge(
			$replace,
			[
				'<?php' => '<?php' . PHP_EOL . PHP_EOL . '// copy from ../vendor/composer/composer/bin/composer with changed path to bootstrap.php, custom Application object and set setAutoExit(false)',
				'use Composer\Console\Application;' => 'use Forrest79\ComposerNY\Console\Application; // @ComposerNY update',
				'$application->run();' => '// @ComposerNY update' . PHP_EOL . '$application->setAutoExit(false);' . PHP_EOL . 'exit($application->run());',
			],
		));

		file_put_contents(__DIR__ . '/../bin/composer', $binComposer);

		$binCompiler = file_get_contents(__DIR__ . '/../vendor/composer/composer/bin/compile');
		if ($binCompiler === FALSE) {
			throw new \RuntimeException('Can\'t load original compile binary.');
		}

		$binCompiler = strtr($binCompiler, array_merge(
			$replace,
			[
				'<?php' => '<?php' . PHP_EOL . PHP_EOL . '// copy from ../vendor/composer/composer/bin/compile with changed Compiler object and path to bootstrap.php',
				'use Composer\Compiler;' => 'use Forrest79\ComposerNY\Compiler; // @ComposerNY update',
			],
		));

		file_put_contents(__DIR__ . '/../bin/compile', $binCompiler);

		if (file_exists($pharFile)) {
			unlink($pharFile);
		}

		$phar = new \Phar($pharFile, 0, 'composer.phar');
		$phar->setSignatureAlgorithm(\Phar::SHA512);

		$phar->startBuffering();

		$finderSort = static function ($a, $b): int {
			return strcmp(strtr($a->getRealPath(), '\\', '/'), strtr($b->getRealPath(), '\\', '/'));
		};

		// Add Composer sources
		$finder = new Finder();
		$finder->files()
			->ignoreVCS(TRUE)
			->name('*.php')
			->notName('Compiler.php')
			->in(__DIR__)
			->sort($finderSort);

		foreach ($finder as $file) {
			$this->addFile($phar, $file);
		}

		// Add vendor files
		$finder = new Finder();
		$finder->files()
			->ignoreVCS(TRUE)
			->notPath('/\/(composer\.(json|lock)|[A-Z]+\.md|\.gitignore|appveyor.yml|phpunit\.xml\.dist|phpstan\.neon\.dist|phpstan-config\.neon|phpstan-baseline\.neon)$/')
			->notPath('/bin\/(jsonlint|validate-json|simple-phpunit|phpstan|phpstan\.phar)(\.bat)?$/')
			->notPath('symfony/console/Resources/completion.bash')
			->notPath('justinrainbow/json-schema/demo/')
			->notPath('justinrainbow/json-schema/dist/')
			->notPath('nette/neon/contributing.md')
			->notPath('nette/neon/license.md')
			->notPath('nette/neon/readme.md')
			->notPath('bin/composer')
			->notPath('bin/neon-lint')
			->notPath('composer/installed.json')
			->notPath('composer/CODE_OF_CONDUCT.md')
			->notPath('composer/PORTING_INFO')
			->notPath('composer/UPGRADE-2.0.md')
			->notPath('composer/LICENSE')
			->notPath('composer/bin')
			->notPath('composer/doc')
			->notPath('composer/res')
			->notPath('composer/composer/src/Composer/Autoload/ClassLoader.php')
			->notPath('composer/composer/src/Composer/InstalledVersions.php')
			->notName('')
			->exclude('Tests')
			->exclude('tests')
			->exclude('test')
			->exclude('docs')
			->in(__DIR__ . '/../vendor/')
			->sort($finderSort);

		$extraFiles = [];
		foreach ([
			__DIR__ . '/../vendor/composer/spdx-licenses/res/spdx-exceptions.json',
			__DIR__ . '/../vendor/composer/spdx-licenses/res/spdx-licenses.json',
			CaBundle::getBundledCaBundlePath(),
			__DIR__ . '/../vendor/symfony/console/Resources/bin/hiddeninput.exe',
		] as $file) {
			$extraFiles[$file] = realpath($file);
			if (!file_exists($file)) {
				throw new \RuntimeException('Extra file listed is missing from the filesystem: ' . $file);
			}
		}
		$unexpectedFiles = [];

		foreach ($finder as $file) {
			if (($index = array_search($file->getRealPath(), $extraFiles, TRUE)) !== FALSE) {
				unset($extraFiles[$index]);
			} elseif (!Preg::isMatch('{(^LICENSE$|\.php$)}', $file->getFilename())) {
				$unexpectedFiles[] = (string) $file;
			}

			if (Preg::isMatch('{\.php[\d.]*$}', $file->getFilename())) {
				$this->addFile($phar, $file);
			} else {
				$this->addFile($phar, $file, FALSE);
			}
		}

		if (count($extraFiles) > 0) {
			throw new \RuntimeException('These files were expected but not added to the phar, they might be excluded or gone from the source package:' . PHP_EOL . var_export($extraFiles, TRUE));
		}
		if (count($unexpectedFiles) > 0) {
			throw new \RuntimeException('These files were unexpectedly added to the phar, make sure they are excluded or listed in $extraFiles:' . PHP_EOL . var_export($unexpectedFiles, TRUE));
		}

		// Add runtime utilities separately to make sure they retains the docblocks as these will get copied into projects
		$this->addFile($phar, new \SplFileInfo(__DIR__ . '/../vendor/composer/composer/src/Composer/Autoload/ClassLoader.php'), FALSE);
		$this->addFile($phar, new \SplFileInfo(__DIR__ . '/../vendor/composer/composer/src/Composer/InstalledVersions.php'), FALSE);

		// Add Composer resources
		$finder = new Finder();
		$finder->files()
			->in(__DIR__ . '/../vendor/composer/composer/res')
			->sort($finderSort);

		foreach ($finder as $file) {
			$this->addFile($phar, $file, FALSE);
		}

		// Add bin/composer
		$this->addComposerBin($phar);

		// Stubs
		$phar->setStub($this->getStub());

		$phar->stopBuffering();

		$phar->compressFiles(\Phar::GZ);

		$this->addFile($phar, new \SplFileInfo(__DIR__ . '/../vendor/composer/composer/LICENSE'), FALSE);

		unset($phar);

		// re-sign the phar with reproducible timestamp / signature
		$util = new Timestamps($pharFile);
		$util->save($pharFile, \Phar::SHA512);

		Linter::lint($pharFile, [
			'vendor/symfony/console/Attribute/AsCommand.php',
			'vendor/symfony/polyfill-intl-grapheme/bootstrap80.php',
			'vendor/symfony/polyfill-intl-normalizer/bootstrap80.php',
			'vendor/symfony/polyfill-mbstring/bootstrap80.php',
			'vendor/symfony/polyfill-php73/Resources/stubs/JsonException.php',
			'vendor/symfony/service-contracts/Attribute/SubscribedService.php',
		]);
	}


	private function getRelativeFilePath(\SplFileInfo $file): string
	{
		$realPath = $file->getRealPath();
		$pathPrefix = dirname(__DIR__) . DIRECTORY_SEPARATOR;

		$pos = strpos($realPath, $pathPrefix);
		$relativePath = ($pos !== FALSE) ? substr_replace($realPath, '', $pos, strlen($pathPrefix)) : $realPath;

		return strtr($relativePath, '\\', '/');
	}


	private function addFile(\Phar $phar, \SplFileInfo $file, bool $strip = TRUE): void
	{
		$path = $this->getRelativeFilePath($file);
		$content = file_get_contents((string) $file);
		assert($content !== FALSE);

		if ($strip) {
			$content = $this->stripWhitespace($content);
		} else if ($file->getFilename() === 'LICENSE') {
			$content = "\n" . $content . "\n";
		}

		$phar->addFromString($path, $content);
	}


	private function addComposerBin(\Phar $phar): void
	{
		$content = file_get_contents(__DIR__ . '/../bin/composer');
		$content = Preg::replace('{^#!/usr/bin/env php\s*}', '', $content);
		$phar->addFromString('bin/composer', $content);
	}


	/**
	 * Removes whitespace from a PHP source string while preserving line numbers.
	 *
	 * @param string $source A PHP string
	 * @return string The PHP string with the whitespace removed
	 */
	private function stripWhitespace(string $source): string
	{
		if (!function_exists('token_get_all')) {
			return $source;
		}

		$output = '';
		foreach (token_get_all($source) as $token) {
			if (is_string($token)) {
				$output .= $token;
			} else if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], TRUE)) {
				$output .= str_repeat("\n", substr_count($token[1], "\n"));
			} else if ($token[0] === T_WHITESPACE) {
				// reduce wide spaces
				$whitespace = Preg::replace('{[ \t]+}', ' ', $token[1]);
				// normalize newlines to \n
				$whitespace = Preg::replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
				// trim leading spaces
				$whitespace = Preg::replace('{\n +}', "\n", $whitespace);
				$output .= $whitespace;
			} else {
				$output .= $token[1];
			}
		}

		return $output;
	}


	private function getStub(): string
	{
		return <<<'EOF'
#!/usr/bin/env php
<?php
/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view
 * the license that is located at the bottom of this file.
 */

// Avoid APC causing random fatal errors per https://github.com/composer/composer/issues/264
if (extension_loaded('apc') && filter_var(ini_get('apc.enable_cli'), FILTER_VALIDATE_BOOLEAN) && filter_var(ini_get('apc.cache_by_default'), FILTER_VALIDATE_BOOLEAN)) {
    if (version_compare(phpversion('apc'), '3.0.12', '>=')) {
        ini_set('apc.cache_by_default', 0);
    } else {
        fwrite(STDERR, 'Warning: APC <= 3.0.12 may cause fatal errors when running composer commands.'.PHP_EOL);
        fwrite(STDERR, 'Update APC, or set apc.enable_cli or apc.cache_by_default to 0 in your php.ini.'.PHP_EOL);
    }
}

if (!class_exists('Phar')) {
    echo 'PHP\'s phar extension is missing. Composer requires it to run. Enable the extension or recompile php without --disable-phar then try again.' . PHP_EOL;
    exit(1);
}

Phar::mapPhar('composer.phar');

require 'phar://composer.phar/bin/composer';

__HALT_COMPILER();
EOF;
	}

}
