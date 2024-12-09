<?php declare(strict_types=1);

namespace Forrest79\ComposerYamlNeon;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

final class Plugin implements PluginInterface
{

    public function activate(Composer $composer, IOInterface $io): void
    {
//        $installer = new TemplateInstaller($io, $composer);
//        $composer->getInstallationManager()->addInstaller($installer);
    }


	public function deactivate(Composer $composer, IOInterface $io): void
	{
		// TODO: Implement deactivate() method.
	}


	public function uninstall(Composer $composer, IOInterface $io): void
	{
		// TODO: Implement uninstall() method.
	}

}
