<?php declare(strict_types=1);
/*
 * This file is part of the jojo1981/data-resolver-handlers package
 *
 * Copyright (c) 2019 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace Jojo1981\DataResolverHandlers;

use Jojo1981\DataResolver\Handler\Exception\HandlerException;
use Jojo1981\DataResolver\Handler\SequenceHandlerInterface;
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
    final public function supports($data): bool
    {
        $type = $this->getSupportedType();

        return $data instanceof $type;
    }

    /**
     * @param mixed $data
     * @throws HandlerException
     * @return Traversable
     */
    final public function getIterator($data): Traversable
    {
        if (!$this->supports($data)) {
            $this->throwUnsupportedException('getIterator');
        }

        return $this->performGetIterator($data);
    }

    /**
     * @param mixed $data
     * @param callable $callback
     * @throws HandlerException
     * @return mixed
     */
    final public function filter($data, callable $callback)
    {
        if (!$this->supports($data)) {
            $this->throwUnsupportedException('filter');
        }

        return $this->performFilter($data, $callback);
    }

    /**
     * @param mixed $data
     * @param callable $callback
     * @throws HandlerException
     * @return mixed
     */
    final public function flatten($data, callable $callback)
    {
        if (!$this->supports($data)) {
            $this->throwUnsupportedException('flatten');
        }

        return $this->performFlatten($data, $callback);
    }

    /**
     * @param mixed $data
     * @throws HandlerException
     * @return int
     */
    final public function count($data): int
    {
        if (!$this->supports($data)) {
            $this->throwUnsupportedException('count');
        }

        return $this->performCount($data);
    }

    /**
     * @param string $methodName
     * @throws HandlerException
     * @return void
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
    abstract protected function performGetIterator($data): Traversable;

    /**
     * @param mixed $data
     * @param callable $callback
     * @return mixed
     */
    abstract protected function performFilter($data, callable $callback);

    /**
     * @param mixed $data
     * @param callable $callback
     * @return mixed
     */
    abstract protected function performFlatten($data, callable $callback);

    /**
     * @param mixed $data
     * @return int
     */
    abstract protected function performCount($data): int;
}
