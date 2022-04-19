# MonoComposer

MonoComposer is a tool for distribution global vendor to current project inside a repository.

For this use case, it recommended commit vendor to your repository, but add rule like is in [composer manual](https://getcomposer.org/doc/faqs/should-i-commit-the-dependencies-in-my-vendor-directory.md)

For this moment this tool can do nothing more, then take global vendor move to project, generate composer.lock and composer.json and execute `composer install`. You can add optional argument like `-a --no-dev`.

## Structure of composer.neon
```yaml
composer:
	install:
		- --no-dev # optional parameters for composer install command
	
	# same field like is in composer.json these fields add to all projects
	homepage: http://www.csfd.cz/vyvojari/
	require:
		- php
	
# projects with own composer.json, structure is same
composers:
	apps/wiki:
		name: my/wiki
		require:
			- nette/application
		require-dev:
			- nette/tester
		autoload:
			psr-4:
				My\Wiki\: app/src/
		autoload-dev:
			psr-4:
				My\Wiki\Tests\: app/tests/src

# list if available packages for projects
packages:
	require:
		php: '>=7.3'
		nette/application: ^2.4
		nette/tester: 2.0.*

	repositories:
		-
			type: vcs
			url: "https://github.com/my/repository.git"
```

## Step by step how add package

Synchronization between **composer.neon** and global **composer.json** is manually. @todo

```bash
composer require nette/application 
```
this create line in composer.json for example **"nette/application": "^2.4"** take it and add to your project
```yaml
composers:
	apps/wiki:
		require:
			- nette/application
```
and add to the list of packages
```yaml
packages:
	require:
		nette/application: ^2.4
```

## Todo
- generate composer.json from composer.neon @todo done
- check what packages are not used in projects
- sync composer.json and composer.neon like composer's commands (require, install, update)
- reverse operation for monocomposer:install
