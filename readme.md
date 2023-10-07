# ComposerNY

[![Build](https://github.com/forrest79/ComposerNY/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/forrest79/ComposerNY/actions/workflows/build.yml)

## tl;dr

Use classic [Composer](https://github.com/composer/composer) with definition file in [NEON](https://ne-on.org/) (`composer.neon`) or [YAML](https://yaml.org/) (`composer.yaml`/`composer.yml`) format instead of JSON.

## How to use it

When you use `composer.json` definition file, ComposerNY acts like a classic composer. But with ComposerNY you can use `composer.neon` or `composer.yaml`/`composer.yml` instead of `composer.json`.

For example `YAML` format:

```yaml
# You can use comments...

name: forrest79/composer-ny # ...or this comments

authors:
    -
        name: 'Jakub Trmota'
        email: jakub@trmota.cz

require:
    composer/composer: 2.3.6
    php: '>=8.0'

require-dev:
    squizlabs/php_codesniffer: ^3.5

autoload:
    psr-4:
        Forrest79\ComposerNY\: src

bin:
    - bin/composer

scripts:
    phpcs: 'vendor/bin/phpcs -s src'

config:
    allow-plugins:
        dealerdirect/phpcodesniffer-composer-installer: false
```

or `NEON` format:

```
# You can use comments...

name: forrest79/composer-ny # ...or this comments

authors:
	-
		name: Jakub Trmota
		email: jakub@trmota.cz

require:
	composer/composer: '2.3.6'
	php: '>=8.0'

require-dev:
	squizlabs/php_codesniffer: ^3.5

autoload:
	psr-4:
		Forrest79\ComposerNY\: src

bin:
	- bin/composer

scripts:
	phpcs: vendor/bin/phpcs -s src

config:
	allow-plugins:
		dealerdirect/phpcodesniffer-composer-installer: false
```

> IMPORTANT: You can use only one definition file in a directory.

## How does it work?

Simply! At startup is classic `composer.json` generated from YAML or NEON definition file and at the end is JSON file cleaned. That's the magic.

You can keep generated `composer.json` by calling `composer generate-json`.

When `composer.json` is changed by Composer (i.e., after `composer require` command etc.), new definition file in YAML or NEON format is saved next to the original definition file, and you must make manual diff and merge.

## Installation

There is no installation script or self-update command for ComposerNY. You must install or update it manually. Versions correspond to the original Composer versions, and only the current Composer line is supported.

You can manually download compiled `phar` from releases or replace original Composer. For example, on Linux:

```bash
$ which composer # ie. `/usr/local/bin/composer`
$ sudo wget -O /usr/local/bin/composer https://github.com/forrest79/ComposerNY/releases/download/v2.6.5/composer.phar
```
