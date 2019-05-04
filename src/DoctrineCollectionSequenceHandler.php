<?php
/*
 * This file is part of the jojo1981/data-resolver-handlers package
 *
 * Copyright (c) 2019 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace Jojo1981\DataResolverHandlers;

use Doctrine\Common\Collections\Collection;
use Jojo1981\DataResolver\Handler\Exception\HandlerException;
use Jojo1981\DataResolver\Handler\SequenceHandlerInterface;

/**
 * @package Jojo1981\DataResolverHandlers
 */
class DoctrineCollectionSequenceHandler implements SequenceHandlerInterface
{
    /**
     * @param mixed $data
     * @return bool
     */
    public function supports($data): bool
    {
        return $data instanceof Collection;
    }

    /**
     * @param mixed|Collection $data
     * @throws HandlerException
     * @return \Traversable
     */
    public function getIterator($data): \Traversable
    {
        if (!$this->supports($data)) {
            $this->throwUnsupportedException('getIterator');
        }

        return $data->getIterator();
    }

    /**
     * @param mixed|Collection $data
     * @param callable $callback
     * @throws HandlerException
     * @return Collection
     */
    public function filter($data, callable $callback): Collection
    {
        if (!$this->supports($data)) {
            $this->throwUnsupportedException('filter');
        }

        return $data->filter($callback);
    }

    /**
     * @param mixed|Collection $data
     * @param callable $callback
     * @throws HandlerException
     * @return Collection
     */
    public function flatten($data, callable $callback): Collection
    {
        if (!$this->supports($data)) {
            $this->throwUnsupportedException('flatten');
        }

        $result = clone $data;
        $result->clear();

        foreach ($data as $key => $value) {
            $items = (array) $callback($key, $value);
            foreach ($items as $item) {
                $result->add($item);
            }
        }

        return $result;
    }

    /**
     * @param string $methodName
     * @throws HandlerException
     * @return void
     */
    private function throwUnsupportedException(string $methodName): void
    {
        throw new HandlerException(\sprintf(
            'The `%s` can only handle instances of `%s`. Illegal invocation of method `%s`. You should invoke the `%s` method first!',
            __CLASS__,
            Collection::class,
            $methodName,
            'supports'
        ));
    }
}