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

use Jojo1981\TypedCollection\Collection;
use Jojo1981\TypedCollection\Exception\CollectionException;
use Jojo1981\TypedCollection\TypeChecker;

/**
 * @package Jojo1981\DataResolverHandlers
 */
class TypedCollectionSequenceHandler extends AbstractCollectionSequenceHandler
{
    /**
     * @return string
     */
    protected function getSupportedType(): string
    {
        return Collection::class;
    }

    /**
     * @param mixed|Collection $data
     * @return \Traversable
     */
    protected function performGetIterator($data): \Traversable
    {
        return $data->getIterator();
    }

    /**
     * @param mixed|Collection $data
     * @param callable $callback
     * @throws CollectionException
     * @return Collection
     */
    protected function performFilter($data, callable $callback): Collection
    {
        return $data->filter($callback);
    }

    /**
     * @param mixed|Collection $data
     * @param callable $callback
     * @throws CollectionException
     * @return Collection
     */
    protected function performFlatten($data, callable $callback): Collection
    {
        $elements = [];
        $type = $data instanceof Collection ? $data->getType() : null;
        foreach ($data as $key => $value) {
            $callbackResult = $callback($key, $value);
            if ($callbackResult instanceof Collection) {
                $type = $type ?? $callbackResult->getType();
                $callbackResult = $callbackResult->toArray();
            }

            $callbackResult = \is_array($callbackResult) ? $callbackResult : (array) $callbackResult;

            if (!empty($callbackResult)) {
                $type = TypeChecker::getType(\end($callbackResult));
                \array_push($elements, ...$callbackResult);
            }
        }

        return new Collection($type, $elements);
    }
}