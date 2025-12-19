<?php
/*
 * This file is part of the jojo1981/data-resolver-handlers package
 *
 * Copyright (c) 2019 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
declare(strict_types=1);

namespace Jojo1981\DataResolverHandlers;

use Jojo1981\DataResolver\Handler\Exception\HandlerException;
use Jojo1981\DataResolver\Handler\SequenceHandlerInterface;
use Jojo1981\TypedCollection\Exception\CollectionException;
use Traversable;
use function get_class;
use function sprintf;

/**
 * @package Jojo1981\DataResolverHandlers
 */
abstract class AbstractCollectionSequenceHandler implements SequenceHandlerInterface
{
    /**
     * @param mixed $data
     * @return bool
     */
    final public function supports(mixed $data): bool
    {
        $type = $this->getSupportedType();

        return $data instanceof $type;
    }

    /**
     * @param mixed $data
     * @return Traversable
     * @throws HandlerException
     */
    final public function getIterator(mixed $data): Traversable
    {
        if (!$this->supports($data)) {
            $this->throwUnsupportedException('getIterator');
        }

        return $this->performGetIterator($data);
    }

    /**
     * @param mixed $data
     * @param callable $callback
     * @return mixed
     * @throws HandlerException
     */
    final public function filter(mixed $data, callable $callback): mixed
    {
        if (!$this->supports($data)) {
            $this->throwUnsupportedException('filter');
        }

        return $this->performFilter($data, $callback);
    }

    /**
     * @param mixed $data
     * @param callable $callback
     * @return mixed
     * @throws HandlerException
     * @throws CollectionException
     */
    final public function flatten(mixed $data, callable $callback): mixed
    {
        if (!$this->supports($data)) {
            $this->throwUnsupportedException('flatten');
        }

        return $this->performFlatten($data, $callback);
    }

    /**
     * @param mixed $data
     * @return int
     * @throws HandlerException
     */
    final public function count(mixed $data): int
    {
        if (!$this->supports($data)) {
            $this->throwUnsupportedException('count');
        }

        return $this->performCount($data);
    }

    /**
     * @param string $methodName
     * @return void
     * @throws HandlerException
     */
    private function throwUnsupportedException(string $methodName): void
    {
        throw new HandlerException(sprintf(
            'The `%s` can only handle instances of `%s`. Illegal invocation of method `%s`. You should invoke ' .
            'the `%s` method first!',
            get_class($this),
            $this->getSupportedType(),
            $methodName,
            'supports'
        ));
    }

    /**
     * @return string
     */
    abstract protected function getSupportedType(): string;

    /**
     * @param mixed $data
     * @return Traversable
     */
    abstract protected function performGetIterator(mixed $data): Traversable;

    /**
     * @param mixed $data
     * @param callable $callback
     * @return mixed
     */
    abstract protected function performFilter(mixed $data, callable $callback): mixed;

    /**
     * @param mixed $data
     * @param callable $callback
     * @throws CollectionException
     * @return mixed
     */
    abstract protected function performFlatten(mixed $data, callable $callback): mixed;

    /**
     * @param mixed $data
     * @return int
     */
    abstract protected function performCount(mixed $data): int;
}
