# Handlers for the data resolver 
=====================

[![Build Status](https://travis-ci.com/jojo1981/data-resolver-handlers.svg?branch=master)](https://travis-ci.com/jojo1981/data-resolver-handlers)
[![Coverage Status](https://coveralls.io/repos/github/jojo1981/data-resolver-handlers/badge.svg)](https://coveralls.io/github/jojo1981/data-resolver-handlers)
[![Latest Stable Version](https://poser.pugx.org/jojo1981/data-resolver-handlers/v/stable)](https://packagist.org/packages/jojo1981/data-resolver-handlers)
[![Total Downloads](https://poser.pugx.org/jojo1981/data-resolver-handlers/downloads)](https://packagist.org/packages/jojo1981/data-resolver-handlers)
[![License](https://poser.pugx.org/jojo1981/data-resolver-handlers/license)](https://packagist.org/packages/jojo1981/data-resolver-handlers)

Author: Joost Nijhuis <[jnijhuis81@gmail.com](mailto:jnijhuis81@gmail.com)>

This library is an extension for `jojo1981/data-resolver` and contains custom handlers which add support to work with some 3th party libraries.

This library a support for:
- instances of `Doctrine\Common\Collections\Collection` from the package `doctrine/collections`.

## Installation

### Library

```bash
git clone https://github.com/jojo1981/data-resolver-handlers.git
```

### Composer

[Install PHP Composer](https://getcomposer.org/doc/00-intro.md)

```bash
composer require jojo1981/data-resolver-handlers
```

## Usage

```php
<?php

require 'vendor/autoload.php';

// get handler factory and set sequence handlers
$handlerFactory = new \Jojo1981\DataResolver\Factory\HandlerFactory();
$handlerFactory->setSequenceHandlers([
    new \Jojo1981\DataResolverHandlers\DoctrineCollectionSequenceHandler(),
    new \Jojo1981\DataResolver\Handler\SequenceHandler\ArraySequenceHandler()
]);

// get main factory and inject handler factory
$factory = new \Jojo1981\DataResolver\Factory();
$factory->setHandlerFactory($handlerFactory);

// get resolver builder factory
$resolverBuilderFactory = $factory->getResolverBuilderFactory();

// and you're ready to go
```