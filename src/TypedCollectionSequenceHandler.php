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

use Jojo1981\DataResolver\Handler\Exception\HandlerException;
use Jojo1981\DataResolver\Handler\SequenceHandlerInterface;
use Jojo1981\TypedCollection\Collection;
use Jojo1981\TypedCollection\Exception\CollectionException;
use Jojo1981\TypedCollection\TypeChecker;

/**
 * @package Jojo1981\DataResolverHandlers
 */
class TypedCollectionSequenceHandler implements SequenceHandlerInterface
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
     * @throws CollectionException
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
     * @throws CollectionException
     * @return Collection
     */
    public function flatten($data, callable $callback): Collection
    {
        if (!$this->supports($data)) {
            $this->throwUnsupportedException('flatten');
        }

        $elements = [];
        $type = null;
        foreach ($data as $key => $value) {
            $items = $callback($key, $value);
            if (null === $type){
                if ($items instanceof Collection) {
                    $type = $items->getType();
                } else if (\is_array($items) && !empty($items)) {
                    $type = TypeChecker::getType(\end($items));
                } else {
                    $type = TypeChecker::getType($items);
                }
            }
            if ($items instanceof Collection) {
                $items = $items->toArray();
            }
            if (!\is_array($items)) {
                $items = [$items];
            }

            if (!empty($items)) {
                \array_push($elements, ...$items);
            }
        }

        return new Collection($type, $elements);
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