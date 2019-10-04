Handlers for the data resolver 
=====================

[![Build Status](https://travis-ci.com/jojo1981/data-resolver-handlers.svg?branch=master)](https://travis-ci.com/jojo1981/data-resolver-handlers)
[![Coverage Status](https://coveralls.io/repos/github/jojo1981/data-resolver-handlers/badge.svg)](https://coveralls.io/github/jojo1981/data-resolver-handlers)
[![Latest Stable Version](https://poser.pugx.org/jojo1981/data-resolver-handlers/v/stable)](https://packagist.org/packages/jojo1981/data-resolver-handlers)
[![Total Downloads](https://poser.pugx.org/jojo1981/data-resolver-handlers/downloads)](https://packagist.org/packages/jojo1981/data-resolver-handlers)
[![License](https://poser.pugx.org/jojo1981/data-resolver-handlers/license)](https://packagist.org/packages/jojo1981/data-resolver-handlers)

Author: Joost Nijhuis <[jnijhuis81@gmail.com](mailto:jnijhuis81@gmail.com)>

This library is an extension for the `jojo1981/data-resolver` package and contains custom handlers which add support to work with some 3th party libraries.

This library has support for:
- instances of `Doctrine\Common\Collections\Collection` from the package `doctrine/collections`.
- instances of `Jojo1981\TypedCollection\Collection` from the package `jojo1981/typed-collection`.

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

// get factory and register handlers
$factory = (new \Jojo1981\DataResolver\Factory())
    ->useDefaultSequenceHandlers()
    ->registerSequenceHandler(new \Jojo1981\DataResolverHandlers\DoctrineCollectionSequenceHandler())
    ->registerSequenceHandler(new \Jojo1981\DataResolverHandlers\TypedCollectionSequenceHandler())
    ->setMergeHandler(new \Jojo1981\DataResolverHandlers\TypedCollectionMergeHandlerDecorator(
        new \Jojo1981\DataResolver\Handler\MergeHandler\DefaultMergeHandler()    
    ));

// get resolver builder factory
$resolverBuilderFactory = $factory->getResolverBuilderFactory();

// and you're ready to go
```